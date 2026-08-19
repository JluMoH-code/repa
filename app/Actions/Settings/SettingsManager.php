<?php

namespace App\Actions\Settings;

use App\Models\Setting;

/**
 * Настройки магазина (контакты, «О магазине») из таблицы settings
 * с дефолтами для свежей базы. Регистрируется как singleton:
 * значения кэшируются на время запроса.
 */
class SettingsManager
{
    private const DEFAULTS = [
        'phone' => '+7 (995) 030-84-46',
        'email' => 'info@repa.ru',
        'address' => 'ул. Поддубного 1, Волгоград',
        'work_hours' => 'Пн-Пт: 9:00 - 18:00',
        'about_text' => 'Интернет-магазин семян Repa — семена овощей, зелени, цветов и бобовых от проверенных производителей. Только оригинальная продукция, быстрая доставка в любую точку России.',
    ];

    /** @var array<string, string>|null */
    private ?array $cache = null;

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $stored = Setting::query()->pluck('value', 'key')->all();

        return $this->cache = array_merge(self::DEFAULTS, $stored);
    }

    public function set(string $key, ?string $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );

        $this->cache = null;
    }
}
