<?php

namespace App\Models;

use App\Actions\Content\SafeUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class MenuLink extends Model
{
    protected $fillable = ['label', 'url', 'location', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $link) {
            if (! $link->url || ! SafeUrl::valid($link->url)) {
                throw ValidationException::withMessages(['url' => 'Небезопасная ссылка.']);
            }
            if (! in_array($link->location, ['header', 'footer'], true)) {
                throw ValidationException::withMessages(['location' => 'Выберите расположение меню.']);
            }
        });
    }

    public static function publicLinks(string $location)
    {
        return static::where('is_active', true)->where('location', $location)->orderBy('sort_order')->get()->filter(function ($link) {
            $path = parse_url($link->url, PHP_URL_PATH);
            if (preg_match('#^/(pages|articles)/([^/]+)$#', $path ?? '', $matches)) {
                return ContentPage::published()->where('kind', $matches[1] === 'pages' ? 'page' : 'article')->where('slug', rawurldecode($matches[2]))->exists();
            }

            return true;
        });
    }
}
