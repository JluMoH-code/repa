<?php

namespace App\Actions\Orders;

use App\Actions\Cart\CartManager;
use App\Actions\Inventory\InventoryManager;
use App\Enums\OrderDeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Создание заказа из текущей корзины.
 *
 * - Гость: позиции берутся из `Session::get('cart')`, сессия очищается.
 * - Авторизованный: позиции из таблицы `carts`, она очищается.
 *
 * Регистрируется как singleton в AppServiceProvider.
 */
class OrderManager
{
    /**
     * Статусы, при которых покупатель может отменить/отредактировать заказ
     * (до этапа отправки).
     *
     * @var array<int, OrderStatus>
     */
    private const CUSTOMER_EDITABLE_STATUSES = [
        OrderStatus::New,
        OrderStatus::Processing,
        OrderStatus::Paid,
    ];

    public function __construct(
        private readonly AuthFactory $auth,
        private readonly CartManager $cart,
        private readonly InventoryManager $inventory,
    ) {}

    /**
     * Оформить заказ из текущей корзины.
     *
     * @param  array{customer_name: string, customer_email: string, customer_phone: string}  $customer
     * @param  array{delivery_city?: string|null, delivery_address?: string|null, delivery_postcode?: string|null}  $delivery
     *
     * @throws EmptyCartException
     * @throws ProductUnavailableException
     * @throws RuntimeException если выбран способ «Доставка по адресу» (в разработке)
     */
    public function createFromCart(array $customer, array $delivery, ?string $comment = null, OrderDeliveryMethod $deliveryMethod = OrderDeliveryMethod::Pickup): Order
    {
        if ($deliveryMethod === OrderDeliveryMethod::Delivery) {
            throw new RuntimeException(
                'Доставка по адресу находится в разработке — пока доступен только самовывоз по адресу магазина.'
            );
        }

        $user = $this->auth->guard()->check() ? $this->auth->user() : null;

        $lines = $this->cart->lines();

        if ($lines->isEmpty()) {
            throw new EmptyCartException('Корзина пуста — нельзя оформить заказ.');
        }

        // Повторная проверка доступности: пока покупатель заполнял форму,
        // товар могли снять с продажи или закончиться. Защита от заказа «в пустоту».
        foreach ($lines as $line) {
            if (! $this->cart->isAvailable($line['product'], $line['variant'])) {
                throw new ProductUnavailableException(
                    'Товар «'.$line['product']->name.'» больше недоступен для заказа.'
                );
            }
        }

        return DB::transaction(function () use ($lines, $customer, $delivery, $comment, $deliveryMethod, $user) {
            $subtotal = $lines->sum(fn (array $line) => $line['line_total']);

            $order = Order::query()->create([
                'user_id' => $user?->id,
                'customer_name' => $customer['customer_name'],
                'customer_email' => $customer['customer_email'],
                'customer_phone' => $this->normalizePhone($customer['customer_phone']),
                // При самовывозе адрес доставки не нужен — храним только способ получения.
                'delivery_method' => $deliveryMethod,
                'delivery_service' => null,
                'delivery_city' => $deliveryMethod === OrderDeliveryMethod::Delivery ? ($delivery['delivery_city'] ?? null) : null,
                'delivery_postcode' => $deliveryMethod === OrderDeliveryMethod::Delivery ? ($delivery['delivery_postcode'] ?? null) : null,
                'delivery_address' => $deliveryMethod === OrderDeliveryMethod::Delivery ? ($delivery['delivery_address'] ?? null) : null,
                'comment' => $comment !== null && trim($comment) !== '' ? trim($comment) : null,
                'status' => OrderStatus::New,
                'subtotal' => $subtotal,
                'total' => $subtotal, // на этом этапе без стоимости доставки
                'placed_at' => now(),
            ]);

            $order->items()->createMany(
                $lines->map(fn (array $line) => [
                    'product_id' => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'product_variant_id' => $line['variant']?->id,
                    'variant_label' => $line['variant']?->label,
                    'price' => $line['price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ])->all()
            );

            foreach ($order->items()->orderBy('product_id')->orderBy('product_variant_id')->get() as $item) {
                $this->inventory->reserve($item, $item->quantity);
            }

            // Очищаем корзину — после транзакции, чтобы при исключении
            // корзина не пропала «в никуда». Делаем это ДО коммита транзакции
            // (transaction closure выполняет всё в одном DB-транзакции, но
            // clear() дёргает отдельные запросы — безопасно).
            if ($user !== null) {
                Cart::query()->where('user_id', $user->id)->delete();
            } else {
                $this->cart->clear();
            }

            return $order->fresh(['items']);
        });
    }

    /**
     * Сменить статус заказа (используется Filament-страницей).
     * Возвращает обновлённый заказ. Валидация переходов — в Order::saving.
     *
     * @throws InvalidStatusTransitionException
     */
    public function changeStatus(Order $order, OrderStatus $newStatus): Order
    {
        return DB::transaction(function () use ($order, $newStatus) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->status !== $newStatus) {
                $locked->status = $newStatus;
                $locked->save();
                $this->inventory->settle($locked, $newStatus);
            }

            return $order->refresh();
        });
    }

    /** Admin edits preserve the original price and change only the reservation delta. */
    public function updateItemQuantities(Order $order, array $quantities): Order
    {
        abort_unless(auth()->user()?->isAdmin() && ! auth()->user()->is_blocked, 403);

        return DB::transaction(function () use ($order, $quantities) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! $this->isEditableByCustomer($locked)) {
                throw new RuntimeException('Состав отправленного или завершённого заказа нельзя изменить.');
            }
            $items = $locked->items()->orderBy('product_id')->orderBy('product_variant_id')->lockForUpdate()->get();
            Validator::make(['quantities' => $quantities], ['quantities' => ['required', 'array'], 'quantities.*' => ['required', 'integer', 'min:1', 'max:99']])->validate();
            foreach ($quantities as $id => $quantity) {
                if (! $items->contains('id', (int) $id)) {
                    throw ValidationException::withMessages(['quantities' => 'Позиция не принадлежит заказу.']);
                }
            }
            foreach ($items as $item) {
                if (! array_key_exists($item->id, $quantities)) {
                    continue;
                }
                // Historical items without reservations keep legacy inventory semantics.
                if ($item->reserved_quantity > 0) {
                    $this->inventory->reserve($item, (int) $quantities[$item->id]);
                }
                $item->quantity = (int) $quantities[$item->id];
                $item->line_total = $item->quantity * $item->price;
                $item->save();
            }
            $subtotal = $locked->items()->sum('line_total');
            $locked->update(['total' => $locked->total - $locked->subtotal + $subtotal, 'subtotal' => $subtotal]);

            return $order->refresh();
        });
    }

    /**
     * Создать аккаунт покупателя из данных гостевого заказа (используется на
     * success-странице после оформления): пользователь задаёт пароль сам.
     * Все гостевые заказы на этот email привязываются к аккаунту.
     *
     * @throws RuntimeException если email уже занят (unique constraint)
     */
    public function createGuestAccount(Order $order, string $password): User
    {
        return DB::transaction(function () use ($order, $password) {
            $user = User::create([
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
                'password' => $password, // хешируется кастом 'hashed'
                'role' => UserRole::Customer,
            ]);

            Order::query()
                ->where('customer_email', $user->email)
                ->whereNull('user_id')
                ->update(['user_id' => $user->id]);

            return $user;
        });
    }

    /**
     * Отмена заказа покупателем — доступна только до отправки
     * (статусы New/Processing/Paid).
     *
     * @throws RuntimeException если заказ уже отправлен или завершён
     */
    public function cancelByCustomer(Order $order): void
    {
        $this->assertEditableByCustomer($order);
        $this->changeStatus($order, OrderStatus::Cancelled);
    }

    /**
     * Обновить контактные данные и способ получения заказа (покупатель,
     * до отправки). Состав заказа не изменяется — цены и снимки фиксированы.
     *
     * @param  array<string, string|null>  $data
     *
     * @throws RuntimeException если заказ уже отправлен/завершён или выбран
     *                          способ «Доставка по адресу» (в разработке)
     */
    public function updateCustomerData(Order $order, array $data): void
    {
        $this->assertEditableByCustomer($order);

        $method = isset($data['delivery_method'])
            ? OrderDeliveryMethod::tryFrom((string) $data['delivery_method'])
            : $order->delivery_method;

        if ($method === OrderDeliveryMethod::Delivery) {
            throw new RuntimeException(
                'Доставка по адресу находится в разработке — пока доступен только самовывоз по адресу магазина.'
            );
        }

        $order->fill([
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $this->normalizePhone((string) $data['customer_phone']),
            'delivery_method' => $method ?? OrderDeliveryMethod::Pickup,
            'delivery_service' => null,
            'delivery_city' => null,
            'delivery_postcode' => null,
            'delivery_address' => null,
            'comment' => isset($data['comment']) && trim((string) $data['comment']) !== ''
                ? trim((string) $data['comment'])
                : null,
        ])->save();
    }

    /**
     * Можно ли покупателю отменить/отредактировать заказ (до отправки).
     */
    public function isEditableByCustomer(Order $order): bool
    {
        return in_array($order->status, self::CUSTOMER_EDITABLE_STATUSES, true);
    }

    /**
     * @throws RuntimeException
     */
    private function assertEditableByCustomer(Order $order): void
    {
        if (! $this->isEditableByCustomer($order)) {
            throw new RuntimeException(
                'Заказ уже отправлен или завершён — отмена и редактирование недоступны.'
            );
        }
    }

    /**
     * Привести телефон к единому формату +7XXXXXXXXXX (как в UpdateUserProfileInformation).
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return $phone;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            $digits = '7'.substr($digits, 1);
        }

        return '+'.$digits;
    }
}
