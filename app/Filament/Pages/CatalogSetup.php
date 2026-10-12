<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class CatalogSetup extends Page
{
    protected string $view = 'filament.pages.catalog-setup';

    protected static ?string $title = 'Настройка каталога';

    protected static ?string $navigationLabel = 'Настройка каталога';

    protected static string|\UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 0;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() && ! auth()->user()->is_blocked;
    }
}
