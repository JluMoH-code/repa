<?php

namespace App\Actions\Orders;

use App\Actions\Cart\CartManager;
use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
