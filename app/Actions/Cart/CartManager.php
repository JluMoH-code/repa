<?php

namespace App\Actions\Cart;

use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * Единая точка работы с корзиной:
 * - гость — данные в сессии (ключ `cart`: [product_id => quantity]);
 * - авторизованный — строки в таблице `carts` (одна строка на товар);
 * - при входе гостевая корзина сливается с корзиной пользователя
 *   (см. App\Listeners\MergeCartOnLogin).
 *
 * Регистрируется как singleton в AppServiceProvider: количества кэшируются
 * на время запроса, чтобы шапка и карточки товаров не делали N+1 запросов.
 */
class CartManager
{
    private const SESSION_KEY = 'cart';

    /** @var array<int, int>|null */
    private ?array $quantitiesCache = null;

    public function __construct(private readonly AuthFactory $auth) {}

    /**
     * Позиции корзины.
     *
     * @return Collection<int, array{product: Product, quantity: int, line_total: int}>
     */
    public function lines(): Collection
    {
        $quantities = $this->quantities();

        if ($quantities === []) {
            return collect();
        }

        $products = Product::query()
            ->with('images')
            ->whereKey(array_keys($quantities))
            ->get()
            ->keyBy('id');

        return collect($quantities)
            ->filter(fn (int $quantity, int $productId): bool => $products->has($productId))
            ->map(fn (int $quantity, int $productId): array => [
                'product' => $products[$productId],
                'quantity' => $quantity,
                'line_total' => $products[$productId]->price * $quantity,
            ])
            ->values();
    }

    /**
     * Добавить товар (количество увеличивается при повторном добавлении).
     */
    public function add(Product $product, int $quantity = 1): void
    {
        $quantity = $this->clamp($quantity);

        if ($this->isGuest()) {
            $items = $this->sessionItems();
            $items[$product->id] = min(Cart::MAX_QUANTITY, ($items[$product->id] ?? 0) + $quantity);
            $this->saveSessionItems($items);
            $this->forgetCache();

            return;
        }

        $item = Cart::query()->firstOrNew([
            'user_id' => $this->auth->id(),
            'product_id' => $product->id,
        ]);
        $item->quantity = min(Cart::MAX_QUANTITY, ($item->exists ? $item->quantity : 0) + $quantity);
        $item->save();
        $this->forgetCache();
    }

    /**
     * Задать количество позиции (абсолютное значение, минимум 1).
     */
    public function updateQuantity(Product $product, int $quantity): void
    {
        $quantity = $this->clamp($quantity);

        if ($this->isGuest()) {
            $items = $this->sessionItems();

            if (! array_key_exists($product->id, $items)) {
                return;
            }

            $items[$product->id] = $quantity;
            $this->saveSessionItems($items);
            $this->forgetCache();

            return;
        }

        Cart::query()
            ->where('user_id', $this->auth->id())
            ->where('product_id', $product->id)
            ->update(['quantity' => $quantity]);
        $this->forgetCache();
    }

    /**
     * Убрать позицию из корзины.
     */
    public function remove(int $productId): void
    {
        if ($this->isGuest()) {
            $items = $this->sessionItems();
            unset($items[$productId]);
            $this->saveSessionItems($items);
            $this->forgetCache();

            return;
        }

        Cart::query()
            ->where('user_id', $this->auth->id())
            ->where('product_id', $productId)
            ->delete();
        $this->forgetCache();
    }

    /**
     * Полностью очистить корзину.
     */
    public function clear(): void
    {
        if ($this->isGuest()) {
            Session::forget(self::SESSION_KEY);
            $this->forgetCache();

            return;
        }

        Cart::query()->where('user_id', $this->auth->id())->delete();
        $this->forgetCache();
    }

    /**
     * Количество единиц товара в корзине (0 — товара нет в корзине).
     * Используется карточками товаров и страницей товара.
     */
    public function quantity(int $productId): int
    {
        return $this->quantities()[$productId] ?? 0;
    }

    /**
     * Есть ли товар в корзине.
     */
    public function contains(Product $product): bool
    {
        if ($this->isGuest()) {
            return array_key_exists($product->id, $this->sessionItems());
        }

        return Cart::query()
            ->where('user_id', $this->auth->id())
            ->where('product_id', $product->id)
            ->exists();
    }

    /**
     * Суммарное количество единиц товара в корзине (счётчик в шапке).
     */
    public function count(): int
    {
        return array_sum($this->quantities());
    }

    /**
     * Итоговая сумма корзины в копейках.
     */
    public function total(): int
    {
        return $this->lines()->sum('line_total');
    }

    /**
     * Можно ли добавить товар в корзину: опубликован, активен и в наличии
     * (у товара с вариантами — хотя бы один вариант в наличии).
     */
    public function isAvailable(Product $product): bool
    {
        if ($product->status !== ProductStatus::Published || ! $product->is_active) {
            return false;
        }

        $product->loadMissing('variants');

        return $product->variants->isNotEmpty()
            ? $product->variants->contains('in_stock', true)
            : (bool) $product->in_stock;
    }

    /**
     * Слить гостевую (сессионную) корзину с корзиной пользователя и очистить сессию.
     * Вызывается при успешном входе (событие Illuminate\Auth\Events\Login).
     *
     * Важно: в момент события пользователь уже авторизован, поэтому очищаем
     * ТОЛЬКО сессионную корзину — `clear()` здесь не подходит (он затирает
     * только что созданные строки БД).
     */
    public function mergeGuestCart(User $user): void
    {
        $sessionItems = $this->sessionItems();

        if ($sessionItems === []) {
            return;
        }

        $products = Product::query()
            ->whereKey(array_keys($sessionItems))
            ->get()
            ->keyBy('id');

        foreach ($sessionItems as $productId => $quantity) {
            $product = $products->get($productId);

            if ($product === null) {
                continue;
            }

            $item = Cart::query()->firstOrNew([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);
            $item->quantity = min(Cart::MAX_QUANTITY, ($item->exists ? $item->quantity : 0) + $quantity);
            $item->save();
        }

        Session::forget(self::SESSION_KEY);
        $this->forgetCache();
    }

    private function isGuest(): bool
    {
        return ! $this->auth->guard()->check();
    }

    /**
     * Текущие количества по product_id (сессия или БД).
     * Кэшируются на время запроса — см. singleton в AppServiceProvider.
     *
     * @return array<int, int>
     */
    private function quantities(): array
    {
        if ($this->quantitiesCache !== null) {
            return $this->quantitiesCache;
        }

        $quantities = $this->isGuest()
            ? $this->sessionItems()
            : Cart::query()
                ->where('user_id', $this->auth->id())
                ->pluck('quantity', 'product_id')
                ->all();

        $this->quantitiesCache = $quantities;

        return $quantities;
    }

    private function forgetCache(): void
    {
        $this->quantitiesCache = null;
    }

    /**
     * @return array<int, int>
     */
    private function sessionItems(): array
    {
        $items = Session::get(self::SESSION_KEY, []);

        return is_array($items) ? $items : [];
    }

    /**
     * @param  array<int, int>  $items
     */
    private function saveSessionItems(array $items): void
    {
        Session::put(self::SESSION_KEY, $items);
    }

    private function clamp(int $quantity): int
    {
        return max(1, min(Cart::MAX_QUANTITY, $quantity));
    }
}
