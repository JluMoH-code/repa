<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id', 'product_variant_id', 'variant_label', 'reserved_quantity',
        'product_name',
        'price',
        'quantity',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quantity' => 'integer', 'reserved_quantity' => 'integer',
            'line_total' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            if ($item->product_variant_id !== null && ! ProductVariant::where('product_id', $item->product_id)->whereKey($item->product_variant_id)->exists()) {
                throw new \InvalidArgumentException('Фасовка не принадлежит товару.');
            }
            if ($item->reserved_quantity < 0 || $item->quantity < 1 || $item->price < 0) {
                throw new \InvalidArgumentException('Количество и цена позиции некорректны.');
            }
        });
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
