<?php

namespace App\Models\Concerns;

use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use InvalidArgumentException;

trait HasInventory
{
    public static function bootHasInventory(): void
    {
        static::saving(function ($model) {
            if ($model->reserved_quantity < 0 || ($model->stock_quantity !== null && ($model->stock_quantity < 0 || $model->reserved_quantity > $model->stock_quantity))) {
                throw new InvalidArgumentException('Остаток не может быть отрицательным или меньше резерва.');
            }
            if ($model->stock_quantity === null && $model->reserved_quantity > 0) {
                throw new InvalidArgumentException('Нельзя отключить учёт товара с резервом.');
            }
        });
        static::deleting(function ($model) {
            $column = $model instanceof ProductVariant ? 'product_variant_id' : 'product_id';
            if ($model->reserved_quantity > 0 || InventoryMovement::where($column, $model->id)->exists()) {
                throw new InvalidArgumentException('Нельзя удалить товар с резервом или историей склада.');
            }
        });
    }

    public function availableQuantity(): ?int
    {
        return $this->stock_quantity === null ? null : $this->stock_quantity - $this->reserved_quantity;
    }

    public function hasAvailableStock(): bool
    {
        return $this->stock_quantity === null ? (bool) $this->in_stock : $this->availableQuantity() > 0;
    }
}
