<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ContentPage extends Model
{
    protected $fillable = ['kind', 'title', 'slug', 'excerpt', 'body', 'status', 'published_at', 'seo_title', 'seo_description'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $page) {
            if (! in_array($page->kind, ['article', 'page'], true) || ! in_array($page->status, ['draft', 'published'], true)) {
                throw ValidationException::withMessages(['status' => 'Некорректный тип или статус.']);
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
