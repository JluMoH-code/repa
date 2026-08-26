<?php

namespace App\Models;

use Database\Factories\FilterGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class FilterGroup extends Model
{
    /** @use HasFactory<FilterGroupFactory> */
    use HasFactory, HasSlug;

    protected $fillable = ['category_id', 'name', 'slug', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate()
            ->extraScope(fn ($query) => $query->where('category_id', $this->category_id));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(FilterValue::class)->orderBy('sort_order');
    }

    protected static function booted(): void
    {
        static::saving(function (self $group) {
            // Группа фильтров привязана только к КОРНЕВОЙ категории — действует
            // для неё и всего её поддерева (см. комментарий в миграции).
            $category = $group->relationLoaded('category')
                ? $group->category
                : Category::find($group->category_id);

            if ($category?->parent_id !== null) {
                throw new InvalidArgumentException(
                    'Группа фильтров привязывается только к корневой категории (она действует для всех её подкатегорий).'
                );
            }

            // Уникальность slug в рамках категории — защита на уровне модели
            // (уникальный индекс [category_id, slug] в БД — вторая линия).
            $duplicate = static::query()
                ->where('category_id', $group->category_id)
                ->where('slug', $group->slug)
                ->when($group->exists, fn ($q) => $q->whereKeyNot($group->id))
                ->exists();

            if ($duplicate) {
                throw new InvalidArgumentException(
                    "Группа фильтров со slug «{$group->slug}» уже существует в этой категории."
                );
            }
        });
    }
}
