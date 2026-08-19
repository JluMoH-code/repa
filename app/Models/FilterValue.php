<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use InvalidArgumentException;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class FilterValue extends Model
{
    /** @use HasFactory<\Database\Factories\FilterValueFactory> */
    use HasFactory, HasSlug;

    protected $fillable = ['filter_group_id', 'value', 'slug', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('value')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate()
            ->extraScope(fn ($query) => $query->where('filter_group_id', $this->filter_group_id));
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(FilterGroup::class, 'filter_group_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'filter_value_product');
    }

    protected static function booted(): void
    {
        static::saving(function (self $value) {
            if (blank($value->filter_group_id)) {
                throw new InvalidArgumentException(
                    'Значение фильтра должно принадлежать группе фильтров.'
                );
            }

            $duplicate = static::query()
                ->where('filter_group_id', $value->filter_group_id)
                ->where('slug', $value->slug)
                ->when($value->exists, fn ($q) => $q->whereKeyNot($value->id))
                ->exists();

            if ($duplicate) {
                throw new InvalidArgumentException(
                    "Значение со slug «{$value->slug}» уже существует в этой группе."
                );
            }
        });
    }
}
