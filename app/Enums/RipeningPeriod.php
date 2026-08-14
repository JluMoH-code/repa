<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RipeningPeriod: string implements HasLabel
{
    case Early = 'early';
    case Mid = 'mid';
    case Late = 'late';

    public function getLabel(): string
    {
        return match ($this) {
            self::Early => 'Раннеспелый',
            self::Mid => 'Среднеспелый',
            self::Late => 'Позднеспелый',
        };
    }
}
