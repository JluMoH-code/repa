<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory, HasSlug;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'sort_order',
        'is_active',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
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

    protected static function booted(): void
    {
        static::deleting(function (self $category) {
            if ($category->products()->exists()) {
                throw new InvalidArgumentException(
                    'Нельзя удалить категорию, в которой есть товары. Сначала перенесите их в другую категорию.'
                );
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function filterGroups(): HasMany
    {
        return $this->hasMany(FilterGroup::class)->orderBy('sort_order');
    }

    /**
     * ID этой категории и всех её прямых подкатегорий — используется, чтобы
     * собрать товары "по всей категории" (кнопка "Все" на странице типа 1).
     *
     * @return array<int, int>
     */
    public function selfAndChildrenIds(): array
    {
        return $this->children->pluck('id')->push($this->id)->all();
    }

    /**
     * Все категории плоским списком с вычисленной глубиной вложенности —
     * удобно для select'ов с отступами в админке.
     *
     * @return Collection<int, self>
     */
    public static function tree(): Collection
    {
        $categories = self::query()->orderBy('sort_order')->orderBy('name')->get();

        $byParent = $categories->groupBy('parent_id');

        $flatten = function ($parentId, int $depth) use (&$flatten, $byParent): Collection {
            return ($byParent->get($parentId) ?? collect())
                ->flatMap(function (self $category) use ($flatten, $depth) {
                    $category->setAttribute('depth', $depth);

                    return collect([$category])->merge($flatten($category->id, $depth + 1));
                });
        };

        return $flatten(null, 0);
    }

    /**
     * Хлебные крошки от корня до текущей категории (включительно).
     *
     * @return Collection<int, self>
     */
    public function breadcrumbs(): Collection
    {
        $chain = collect([$this]);
        $current = $this;

        while ($current->parent_id !== null) {
            $current = $current->parent ?? $current->loadMissing('parent')->parent;

            if ($current === null) {
                break;
            }

            $chain->prepend($current);
        }

        return $chain;
    }

    protected function indentedName(): Attribute
    {
        return Attribute::get(function () {
            $depth = $this->getAttribute('depth') ?? 0;

            return str_repeat('— ', $depth).$this->name;
        });
    }
}
