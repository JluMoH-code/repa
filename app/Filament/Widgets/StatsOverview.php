<?php

namespace App\Filament\Widgets;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Товары', number_format(Product::count(), 0, ',', ' '))
                ->description(
                    Product::where('status', ProductStatus::Published)->count()
                    .' опубликовано, '
                    .Product::where('status', ProductStatus::Draft)->count()
                    .' черновиков'
                )
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),

            Stat::make('Категории', number_format(Category::count(), 0, ',', ' '))
                ->description(Category::whereNull('parent_id')->count().' корневых')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('info'),

            Stat::make('Производители', number_format(Manufacturer::count(), 0, ',', ' '))
                ->descriptionIcon('heroicon-m-building-library')
                ->color('warning'),

            Stat::make('Пользователи', number_format(User::count(), 0, ',', ' '))
                ->description(User::where('role', UserRole::Admin->value)->count().' администраторов')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),
        ];
    }
}
