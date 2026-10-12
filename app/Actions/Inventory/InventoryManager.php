<?php

namespace App\Actions\Inventory;

use App\Enums\OrderStatus;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InventoryManager
{
    public function receive(Product $product, int $quantity, ?ProductVariant $variant = null, ?string $note = null): void
    {
        $this->adjust($product, $variant, 'receipt', $quantity, null, $note);
    }

    /** Set the counted physical balance; zero is a valid count. */
    public function correct(Product $product, int $quantity, ?ProductVariant $variant = null, ?string $note = null): void
    {
        $this->adjust($product, $variant, 'correction', $quantity, null, $note);
    }

    public function offlineSale(Product $product, int $quantity, int $unitPrice, ?ProductVariant $variant = null, ?string $note = null): void
    {
        $this->adjust($product, $variant, 'offline_sale', $quantity, $unitPrice, $note);
    }

    private function adjust(Product $product, ?ProductVariant $variant, string $type, int $quantity, ?int $unitPrice, ?string $note): void
    {
        abort_unless(auth()->user()?->isAdmin() && ! auth()->user()->is_blocked, 403);
        Validator::make(['quantity' => $quantity, 'unit_price' => $unitPrice, 'note' => $note], [
            'quantity' => ['required', 'integer', $type === 'correction' ? 'min:0' : 'min:1', 'max:2147483647'],
            'unit_price' => [$type === 'offline_sale' ? 'required' : 'nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ])->validate();
        DB::transaction(function () use ($product, $variant, $type, $quantity, $unitPrice, $note) {
            $target = $this->target($product->id, $variant?->id);
            if ($type === 'offline_sale' && $target->stock_quantity === null) {
                throw ValidationException::withMessages(['quantity' => 'Сначала введите фактический остаток.']);
            }
            $change = match ($type) {
                'correction' => $quantity - ($target->stock_quantity ?? 0),
                'offline_sale' => -$quantity,
                default => $quantity,
            };
            $this->apply($target, $product->id, $variant?->id, $type, $change, 0, $unitPrice, null, $note);
        });
    }

    /** Called only inside order transactions: the order item is the reservation ledger. */
    public function reserve(OrderItem $item, int $quantity): void
    {
        if (DB::transactionLevel() === 0 || $quantity < 1) {
            throw new \LogicException('Резервирование выполняется только в транзакции заказа.');
        }
        $target = $this->target($item->product_id, $item->product_variant_id);
        if ($target->stock_quantity === null) {
            return;
        }
        $delta = $quantity - $item->reserved_quantity;
        if ($delta === 0) {
            return;
        }
        $this->apply($target, $item->product_id, $item->product_variant_id, 'reservation', 0, $delta, null, $item->order_id);
        $item->reserved_quantity = $quantity;
        $item->save();
    }

    public function settle(Order $order, OrderStatus $status): void
    {
        if (DB::transactionLevel() === 0 || $order->status !== $status) {
            throw new \LogicException('Движение склада выполняется в транзакции смены статуса.');
        }
        $commit = in_array($status, [OrderStatus::Shipped, OrderStatus::Delivered], true);
        $release = in_array($status, [OrderStatus::Cancelled, OrderStatus::Refunded], true);
        if (! $commit && ! $release) {
            return;
        }
        foreach ($order->items()->orderBy('product_id')->orderBy('product_variant_id')->lockForUpdate()->get() as $item) {
            if ($item->reserved_quantity < 1) {
                continue;
            }
            $target = $this->target($item->product_id, $item->product_variant_id);
            $quantity = $item->reserved_quantity;
            $this->apply($target, $item->product_id, $item->product_variant_id, $commit ? 'order_sale' : 'release', $commit ? -$quantity : 0, -$quantity, $commit ? $item->price : null, $order->id);
            $item->reserved_quantity = 0;
            $item->save();
        }
    }

    private function target(int $productId, ?int $variantId): Product|ProductVariant
    {
        $product = Product::query()->lockForUpdate()->findOrFail($productId);

        return $variantId === null ? $product : ProductVariant::query()->where('product_id', $productId)->lockForUpdate()->findOrFail($variantId);
    }

    private function apply(Product|ProductVariant $target, int $productId, ?int $variantId, string $type, int $change, int $reservedChange, ?int $price = null, ?int $orderId = null, ?string $note = null): void
    {
        $stock = ($target->stock_quantity ?? 0) + $change;
        $reserved = $target->reserved_quantity + $reservedChange;
        if ($stock < 0 || $reserved < 0 || $stock < $reserved) {
            throw ValidationException::withMessages(['quantity' => 'Недостаточно свободного остатка. Зарезервированный товар недоступен.']);
        }
        $target->update(['stock_quantity' => $stock, 'reserved_quantity' => $reserved]);
        InventoryMovement::create(['product_id' => $productId, 'product_variant_id' => $variantId, 'type' => $type, 'quantity_change' => $change, 'reserved_change' => $reservedChange, 'unit_price' => $price, 'user_id' => auth()->id(), 'order_id' => $orderId, 'note' => $note]);
    }
}
