<?php

namespace App\Models;

use App\Enums\GrowingPlace;
use App\Enums\ProductStatus;
use App\Enums\RipeningPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, HasSlug;

    /**
     * Фиксированный список культур — единый источник правды для формы в админке
     * и для фабрики (используется при генерации тестовых данных).
     */
    public const CULTURES = [
        'Томаты', 'Огурцы', 'Перец сладкий', 'Перец острый', 'Баклажаны',
        'Капуста белокочанная', 'Капуста цветная', 'Морковь', 'Свёкла',
        'Лук репчатый', 'Чеснок', 'Редис', 'Тыква', 'Кабачок', 'Патиссон',
        'Горох', 'Фасоль', 'Укроп', 'Петрушка', 'Базилик',
        'Астра', 'Бархатцы', 'Петуния', 'Циния', 'Космея',
    ];
    protected $fillable = [
        'category_id',
        'manufacturer_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'short_description',
        'description',
        'price',
        'old_price',
        'unit',
        'seed_count',
        'status',
        'is_active',
        'is_discountable',
        'is_bestseller',
        'is_recommended',
        'culture',
        'ripening',
        'growing_place',
        'is_hybrid',
        'series',
        'attributes',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'old_price' => 'integer',
            'seed_count' => 'integer',
            'status' => ProductStatus::class,
            'ripening' => RipeningPeriod::class,
            'growing_place' => GrowingPlace::class,
            'is_active' => 'boolean',
            'is_discountable' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_recommended' => 'boolean',
            'is_hybrid' => 'boolean',
            'attributes' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $product) {
            if ($product->price !== null && $product->price < 0) {
                throw new InvalidArgumentException('Цена товара не может быть отрицательной.');
            }

            if ($product->old_price !== null && $product->old_price <= $product->price) {
                throw new InvalidArgumentException('Старая цена должна быть больше текущей цены.');
            }

            if ($product->status === ProductStatus::Published) {
                $category = $product->relationLoaded('category')
                    ? $product->category
                    : Category::find($product->category_id);

                if (! $category || ! $category->is_active) {
                    throw new InvalidArgumentException(
                        'Нельзя опубликовать товар в неактивной категории.'
                    );
                }
            }
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function mainImage(): HasMany
    {
        return $this->images()->where('is_main', true);
    }

    /**
     * Товары, которые можно показывать на публичной витрине.
     */
    public function scopeVisible(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', ProductStatus::Published)->where('is_active', true);
    }

    /**
     * Товар можно удалить физически, только если это черновик без изображений —
     * реальное "заказы отсутствуют" будет проверяться на следующем этапе.
     */
    public function isHardDeletable(): bool
    {
        return $this->status === ProductStatus::Draft && $this->images()->doesntExist();
    }
}
