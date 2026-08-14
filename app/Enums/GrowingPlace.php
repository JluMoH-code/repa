<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum GrowingPlace: string implements HasLabel
{
    case OpenGround = 'open_ground';
    case Greenhouse = 'greenhouse';
    case Universal = 'universal';

    public function getLabel(): string
    {
        return match ($this) {
            self::OpenGround => 'Открытый грунт',
            self::Greenhouse => 'Теплица',
            self::Universal => 'Универсально',
        };
    }
}
