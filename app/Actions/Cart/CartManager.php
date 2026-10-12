<?php

namespace App\Actions\Cart;

use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CartManager
{
    private ?array $quantitiesCache = null;

    public function __construct(private readonly AuthFactory $auth) {}

    public static function lineKey(int $productId, ?int $variantId = null): int|string
    {
        return $variantId === null ? $productId : $productId.':'.$variantId;
    }

    private function identity(int|string $key): array
    {
        $parts = explode(':', (string) $key);

        return [(int) $parts[0], isset($parts[1]) ? (int) $parts[1] : null];
    }

    public function lines(): Collection
    {
        $quantities = $this->quantities();
        $ids = array_map(fn ($key) => $this->identity($key)[0], array_keys($quantities));
        $products = Product::with(['images', 'variants'])->whereKey($ids)->get()->keyBy('id');
        foreach ($quantities as $key => $quantity) {
            [$id, $variantId] = $this->identity($key);
            $product = $products->get($id);
            if ($variantId === null && $product && $product->variants->isNotEmpty()) {
                $variant = $this->resolveVariant($product);
                $newKey = self::lineKey($id, $variant->id);
                $merged = min(Cart::MAX_QUANTITY, $quantity + ($quantities[$newKey] ?? 0));
                $this->remove($id);
                $this->write($product, $variant, $merged, $newKey);
                unset($quantities[$key]);
                $quantities[$newKey] = $merged;
            }
        }

        return collect($quantities)->map(function ($quantity, $key) use ($products) {
            [$id, $variantId] = $this->identity($key);
            $product = $products->get($id);
            $variant = $variantId === null ? null : $product?->variants->firstWhere('id', $variantId);
            if (! $product || ($variantId !== null && ! $variant)) {
                return null;
            }

            return ['product' => $product, 'variant' => $variant, 'key' => $key, 'price' => $variant?->price ?? $product->price, 'quantity' => (int) $quantity, 'line_total' => ($variant?->price ?? $product->price) * $quantity];
        })->filter()->values();
    }

    public function add(Product $product, int $quantity = 1, ?ProductVariant $variant = null): void
    {
        $variant = $this->resolveVariant($product, $variant);
        $this->validateVariant($product, $variant);
        $key = self::lineKey($product->id, $variant?->id);
        $quantity = min(Cart::MAX_QUANTITY, $this->quantity($product->id, $variant?->id) + $this->clamp($quantity));
        $this->assertStock($product, $variant, $quantity);
        $this->write($product, $variant, $quantity, $key);
    }

    public function updateQuantity(Product $product, int $quantity, ?ProductVariant $variant = null): void
    {
        $variant = $this->resolveVariant($product, $variant);
        $this->validateVariant($product, $variant);
        if (! $this->contains($product, $variant?->id)) {
            return;
        }
        $quantity = $this->clamp($quantity);
        $this->assertStock($product, $variant, $quantity);
        $this->write($product, $variant, $quantity, self::lineKey($product->id, $variant?->id));
    }

    private function write(Product $product, ?ProductVariant $variant, int $quantity, int|string $key): void
    {
        if ($this->isGuest()) {
            $items = $this->sessionItems();
            $items[$key] = $quantity;
            Session::put('cart', $items);
        } else {
            Cart::updateOrCreate(['user_id' => $this->auth->id(), 'product_id' => $product->id, 'product_variant_id' => $variant?->id], ['quantity' => $quantity]);
        }
        $this->quantitiesCache = null;
    }

    public function remove(int $productId, ?int $variantId = null): void
    {
        if ($this->isGuest()) {
            $items = $this->sessionItems();
            unset($items[self::lineKey($productId, $variantId)]);
            Session::put('cart', $items);
        } else {
            Cart::where('user_id', $this->auth->id())->where('product_id', $productId)->where('product_variant_id', $variantId)->delete();
        }
        $this->quantitiesCache = null;
    }

    public function clear(): void
    {
        if ($this->isGuest()) {
            Session::forget('cart');
        } else {
            Cart::where('user_id', $this->auth->id())->delete();
        }
        $this->quantitiesCache = null;
    }

    public function quantity(int $productId, ?int $variantId = null): int
    {
        return $this->quantities()[self::lineKey($productId, $variantId)] ?? 0;
    }

    public function contains(Product $product, ?int $variantId = null): bool
    {
        return $this->quantity($product->id, $variantId) > 0;
    }

    public function count(): int
    {
        return array_sum($this->quantities());
    }

    public function total(): int
    {
        return $this->lines()->sum('line_total');
    }

    public function isAvailable(Product $product, ?ProductVariant $variant = null): bool
    {
        if ($product->status !== ProductStatus::Published || ! $product->is_active) {
            return false;
        }
        if ($variant !== null) {
            return $variant->product_id === $product->id && $variant->hasAvailableStock();
        }
        $product->loadMissing('variants');

        return $product->variants->isNotEmpty() ? $product->variants->contains(fn ($v) => $v->hasAvailableStock()) : $product->hasAvailableStock();
    }

    private function validateVariant(Product $product, ?ProductVariant $variant): void
    {
        if ($variant !== null && $variant->product_id !== $product->id) {
            throw ValidationException::withMessages(['variant_id' => 'Фасовка не принадлежит этому товару.']);
        }
    }

    public function resolveVariant(Product $product, ?ProductVariant $variant = null): ?ProductVariant
    {
        $this->validateVariant($product, $variant);
        if ($variant !== null) {
            return $variant;
        }
        $product->loadMissing('variants');

        return $product->variants->first(fn ($v) => $v->hasAvailableStock()) ?? $product->variants->first();
    }

    private function assertStock(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        $this->validateVariant($product, $variant);
        $target = $variant ?? $product;
        if ($target->stock_quantity !== null && $quantity > $target->availableQuantity()) {
            throw ValidationException::withMessages(['quantity' => 'Недостаточно свободного остатка.']);
        }
    }

    public function mergeGuestCart(User $user): void
    {
        foreach ($this->sessionItems() as $key => $quantity) {
            [$id, $variantId] = $this->identity($key);
            if (! Product::whereKey($id)->exists() || ($variantId !== null && ! ProductVariant::whereKey($variantId)->where('product_id', $id)->exists())) {
                continue;
            }
            $item = Cart::firstOrNew(['user_id' => $user->id, 'product_id' => $id, 'product_variant_id' => $variantId]);
            $item->quantity = min(Cart::MAX_QUANTITY, ($item->quantity ?? 0) + $quantity);
            $item->save();
        }
        Session::forget('cart');
        $this->quantitiesCache = null;
    }

    public function quantities(): array
    {
        return $this->quantitiesCache ??= $this->isGuest() ? $this->sessionItems() : Cart::where('user_id', $this->auth->id())->get()->mapWithKeys(fn ($row) => [self::lineKey($row->product_id, $row->product_variant_id) => $row->quantity])->all();
    }

    private function sessionItems(): array
    {
        $items = Session::get('cart', []);

        return is_array($items) ? array_filter($items, fn ($quantity, $key) => preg_match('/^\d+(?::\d+)?$/', (string) $key) && is_numeric($quantity) && $quantity > 0, ARRAY_FILTER_USE_BOTH) : [];
    }

    private function isGuest(): bool
    {
        return ! $this->auth->guard()->check();
    }

    private function clamp(int $quantity): int
    {
        return max(1, min(Cart::MAX_QUANTITY, $quantity));
    }
}
