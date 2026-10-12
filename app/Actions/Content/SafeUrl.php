<?php

namespace App\Actions\Content;

class SafeUrl
{
    public static function valid(?string $url): bool
    {
        if ($url === null || $url === '') {
            return true;
        }
        if (preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            return false;
        }
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true);
    }

    public static function rule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (! self::valid($value)) {
                $fail('Укажите путь сайта /catalog или ссылку http/https.');
            }
        };
    }
}
