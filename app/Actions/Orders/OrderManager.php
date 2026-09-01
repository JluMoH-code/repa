<?php

namespace App\Actions\Orders;

use App\Actions\Cart\CartManager;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
    ) {}

    /**
     * Оформить заказ из текущей корзины.
     *
     * @param  array{customer_name: string, customer_email: string, customer_phone: string}  $customer
     * @param  array{delivery_city: string, delivery_address: string, delivery_postcode?: string|null}  $delivery
     *
     * @throws EmptyCartException
     * @throws ProductUnavailableException
     */
    public function createFromCart(array $customer, array $delivery, ?string $comment = null): Order
    {
        $user = $this->auth->guard()->check() ? $this->auth->user() : null;

        $lines = $user !== null
            ? $this->dbLines($user)
            : $this->sessionLines();

        if ($lines->isEmpty()) {
            throw new EmptyCartException('Корзина пуста — нельзя оформить заказ.');
        }

        // Повторная проверка доступности: пока покупатель заполнял форму,
        // товар могли снять с продажи или закончиться. Защита от заказа «в пустоту».
        foreach ($lines as $line) {
            if (! $this->cart->isAvailable($line['product'])) {
                throw new ProductUnavailableException(
                    'Товар «'.$line['product']->name.'» больше недоступен для заказа.'
                );
            }
        }

        return DB::transaction(function () use ($lines, $customer, $delivery, $comment, $user) {
            $subtotal = $lines->sum(fn (array $line) => $line['line_total']);

            $order = Order::query()->create([
                'user_id' => $user?->id,
                'customer_name' => $customer['customer_name'],
                'customer_email' => $customer['customer_email'],
                'customer_phone' => $this->normalizePhone($customer['customer_phone']),
                'delivery_city' => $delivery['delivery_city'],
                'delivery_postcode' => $delivery['delivery_postcode'] ?? null,
                'delivery_address' => $delivery['delivery_address'],
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
                    'price' => $line['product']->price,
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ])->all()
            );

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
        if ($order->status === $newStatus) {
            return $order;
        }

        $order->status = $newStatus;
        $order->save();

        return $order;
    }

    /**
     * @return Collection<int, array{product: Product, quantity: int, line_total: int}>
     */
    private function dbLines(User $user): Collection
    {
        $rows = Cart::query()->where('user_id', $user->id)->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->with('images')
            ->whereKey($rows->pluck('product_id'))
            ->get()
            ->keyBy('id');

        return $rows
            ->map(function (Cart $row) use ($products) {
                $product = $products->get($row->product_id);

                return $product === null
                    ? null
                    : [
                        'product' => $product,
                        'quantity' => $row->quantity,
                        'line_total' => $product->price * $row->quantity,
                    ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, array{product: Product, quantity: int, line_total: int}>
     */
    private function sessionLines(): Collection
    {
        $quantities = $this->cart->quantities();

        if ($quantities === []) {
            return collect();
        }

        $products = Product::query()
            ->with('images')
            ->whereKey(array_keys($quantities))
            ->get()
            ->keyBy('id');

        return collect($quantities)
            ->map(function (int $quantity, int $productId) use ($products) {
                $product = $products->get($productId);

                return $product === null
                    ? null
                    : [
                        'product' => $product,
                        'quantity' => $quantity,
                        'line_total' => $product->price * $quantity,
                    ];
            })
            ->filter()
            ->values();
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
     * Обновить контактные данные и адрес доставки заказа (покупатель,
     * до отправки). Состав заказа не изменяется — цены и снимки фиксированы.
     *
     * @param  array<string, string|null>  $data
     *
     * @throws RuntimeException если заказ уже отправлен или завершён
     */
    public function updateCustomerData(Order $order, array $data): void
    {
        $this->assertEditableByCustomer($order);

        $order->fill([
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $this->normalizePhone((string) $data['customer_phone']),
            'delivery_city' => $data['delivery_city'],
            'delivery_postcode' => $data['delivery_postcode'] ?? null,
            'delivery_address' => $data['delivery_address'],
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
