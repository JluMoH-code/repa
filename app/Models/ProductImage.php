<?php

namespace App\Models;

use Database\Factories\ProductImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'path',
        'sort_order',
        'is_main',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_main' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // На товар может быть только одно главное фото — при простановке
        // is_main у одного изображения снимаем этот флаг у остальных.
        static::saved(function (self $image) {
            if ($image->is_main) {
                static::where('product_id', $image->product_id)
                    ->whereKeyNot($image->id)
                    ->update(['is_main' => false]);
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
