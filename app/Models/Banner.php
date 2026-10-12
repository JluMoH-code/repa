<?php

namespace App\Models;

use App\Actions\Content\SafeUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Banner extends Model
{
    protected $fillable = ['title', 'subtitle', 'image', 'url', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $banner) {
            foreach (['url', 'image'] as $field) {
                if (! SafeUrl::valid($banner->$field)) {
                    throw ValidationException::withMessages([$field => 'Небезопасная ссылка.']);
                }
            }
        });
    }
}
