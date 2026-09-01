<?php

namespace App\Support;

/**
 * Утилиты для русского плюрализации существительных с числительными.
 * Используется в blade'ах вместо trans_choice (в проекте нет lang-файлов,
 * и тянуть trans_choice ради одного-двух слов — overkill).
 */
class RussianPlural
{
    /**
     * Слово «товар» в нужной форме: 1 товар, 2 товара, 5 товаров.
     */
    public static function items(int $count): string
    {
        return self::pluralize($count, 'товар', 'товара', 'товаров');
    }

    /**
     * Универсальный плюрализатор: 1 → one, 2-4 → few, 5+ → many,
     * 11-14 → many (особое правило русского для «десятков»).
     */
    public static function pluralize(int $count, string $one, string $few, string $many): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;

        if ($mod100 >= 11 && $mod100 <= 14) {
            return $many;
        }

        if ($mod10 === 1) {
            return $one;
        }

        if ($mod10 >= 2 && $mod10 <= 4) {
            return $few;
        }

        return $many;
    }
}
