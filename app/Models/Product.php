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
     * Расширен под реалистичный каталог из CatalogSeeder (этап 3).
     */
    public const CULTURES = [
        // Овощи
        'Томаты', 'Огурцы', 'Перец сладкий', 'Перец острый', 'Баклажаны',
        'Капуста белокочанная', 'Капуста цветная', 'Капуста брокколи',
        'Капуста краснокочанная', 'Капуста кольраби', 'Капуста савойская',
        'Капуста брюссельская', 'Морковь', 'Свёкла',
        'Лук репчатый', 'Чеснок', 'Редис', 'Тыква', 'Кабачок', 'Патиссон',
        'Горох', 'Фасоль',
        // Зелень и пряности
        'Укроп', 'Петрушка', 'Базилик', 'Салат', 'Кресс-салат', 'Руккола',
        'Мята', 'Мелисса', 'Тимьян', 'Розмарин', 'Шалфей', 'Душица',
        'Эстрагон', 'Кинза', 'Фенхель', 'Майоран', 'Любисток', 'Иссоп',
        // Цветы — однолетние
        'Астра', 'Бархатцы', 'Петуния', 'Циния', 'Космея', 'Львиный зев',
        'Календула', 'Настурция', 'Портулак', 'Вербена', 'Лобелия',
        'Алиссум', 'Георгина', 'Сальвия', 'Целозия', 'Ипомея', 'Годеция',
        'Эшшольция',
        // Цветы — многолетние
        'Ирис', 'Пион', 'Лилейник', 'Флокс', 'Астильба', 'Хоста', 'Люпин',
        'Дельфиниум', 'Аквилегия', 'Эхинацея', 'Ромашка', 'Примула',
        'Герань', 'Колокольчик', 'Мальва', 'Рудбекия', 'Гвоздика',
        'Лаванда', 'Гейхера',
        // Цветы — ампельные
        'Сурфиния', 'Калибрахоа', 'Бакопа', 'Диасция', 'Пеларгония',
        'Дихондра', 'Бальзамин', 'Фуксия', 'Тунбергия', 'Бегония',
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
        'in_stock',
        'rating',
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
            'in_stock' => 'boolean',
            'rating' => 'float',
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

            // Варианты: основная цена — "цена от", она не может быть выше
            // самой дорогой версии (ловим опечатки оператора, напр. 150000
            // вместо 15000). old_price должен быть выше всех вариантов.
            // Проверяем по БД — не зависит от состояния relation.
            if ($product->getKey() !== null) {
                $maxVariantPrice = (int) ProductVariant::query()
                    ->where('product_id', $product->getKey())
                    ->max('price');

                if ($maxVariantPrice > 0) {
                    if ($maxVariantPrice > $product->price) {
                        throw new InvalidArgumentException(
                            'Основная цена товара должна быть не выше максимальной цены варианта.'
                        );
                    }

                    if ($product->old_price !== null && $product->old_price <= $maxVariantPrice) {
                        throw new InvalidArgumentException(
                            'Старая цена должна быть выше максимальной цены варианта.'
                        );
                    }
                }

                // Назначенные значения фильтров должны принадлежать группам
                // КОРНЕВОЙ категории товара. Корень считаем по ТЕКУЩЕМУ
                // category_id (а не из БД) — guard выполняется до updating-хука,
                // и при смене категории pivot ещё не очищен.
                    $rootCategoryId = self::rootCategoryIdFor($product->category_id);

                    if ($rootCategoryId !== null) {
                        $foreign = \Illuminate\Support\Facades\DB::table('filter_value_product as fvp')
                            ->join('filter_values as fv', 'fv.id', '=', 'fvp.filter_value_id')
                            ->join('filter_groups as fg', 'fg.id', '=', 'fv.filter_group_id')
                            ->where('fvp.product_id', $product->getKey())
                            ->where('fg.category_id', '!=', $rootCategoryId)
                            ->exists();

                        if ($foreign) {
                            throw new InvalidArgumentException(
                                'Значения фильтров должны принадлежать группам корневой категории товара.'
                            );
                        }
                }
            }
        });

        // При смене категории назначенные значения фильтров становятся
        // невалидными (они привязаны к корневой категории) — очищаем pivot,
        // оператор назначает фильтры заново под новую категорию.
        static::updating(function (self $product) {
            if ($product->isDirty('category_id')) {
                $product->filterValues()->detach();
            }
        });
    }

    /**
     * Корневая категория (сама, если корневая, иначе её корень) — источник
     * групп фильтров для товара.
     */
    public function rootCategory(): ?Category
    {
        $rootId = self::rootCategoryIdFor($this->category_id);

        return $rootId !== null ? Category::find($rootId) : null;
    }

    /**
     * ID корневой категории для заданного category_id (по текущему значению
     * модели, а не из БД — важно для guard'ов при смене категории).
     */
    private static function rootCategoryIdFor(?int $categoryId): ?int
    {
        $current = $categoryId;

        while ($current !== null) {
            $parent = Category::query()
                ->whereKey($current)
                ->value('parent_id');

            if ($parent === null) {
                return $current;
            }

            $current = (int) $parent;
        }

        return null;
    }

    /**
     * Группы фильтров корневой категории в виде «Группа => [id => значение]»
     * — для группированного multiselect в форме товара.
     *
     * @return array<string, array<int, string>>
     */
    public function filterOptionGroups(): array
    {
        $root = $this->rootCategory();

        if ($root === null) {
            return [];
        }

        return FilterGroup::query()
            ->where('category_id', $root->id)
            ->with('values')
            ->get()
            ->mapWithKeys(fn (FilterGroup $group) => [
                $group->name => $group->values->pluck('value', 'id')->all(),
            ])
            ->all();
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

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function filterValues(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(FilterValue::class, 'filter_value_product');
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
        return $this->status === ProductStatus::Draft
            && $this->images()->doesntExist()
            && $this->variants()->doesntExist();
    }
}
