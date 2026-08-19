<?php

namespace App\Models;

use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;

    /** Максимальное количество одной позиции в корзине. */
    public const MAX_QUANTITY = 99;

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            if ($item->quantity < 1) {
                throw new InvalidArgumentException('Количество товара в корзине не может быть меньше 1.');
            }

            if ($item->quantity > self::MAX_QUANTITY) {
                throw new InvalidArgumentException(
                    'Количество товара в корзине не может превышать '.self::MAX_QUANTITY.'.'
                );
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
