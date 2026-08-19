<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    protected $fillable = ['product_id', 'label', 'price', 'in_stock', 'sort_order'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'in_stock' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $variant) {
            if (blank($variant->label)) {
                throw new InvalidArgumentException('У варианта товара должно быть название (например, «10 семян»).');
            }

            if ($variant->price !== null && $variant->price < 0) {
                throw new InvalidArgumentException('Цена варианта не может быть отрицательной.');
            }
        });
    }
}
