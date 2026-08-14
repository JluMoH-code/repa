<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Published = 'published';
    case Hidden = 'hidden';
    case Archived = 'archived';
    case Discontinued = 'discontinued';
    case Preorder = 'preorder';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Published => 'Опубликован',
            self::Hidden => 'Скрыт',
            self::Archived => 'В архиве',
            self::Discontinued => 'Снят с продажи',
            self::Preorder => 'Предзаказ',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'success',
            self::Hidden => 'warning',
            self::Archived => 'danger',
            self::Discontinued => 'danger',
            self::Preorder => 'info',
        };
    }
}
