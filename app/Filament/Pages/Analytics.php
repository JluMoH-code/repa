<?php

namespace App\Filament\Pages;

use App\Actions\Analytics\AnalyticsSummary;
use BackedEnum;
use Filament\Pages\Page;

class Analytics extends Page
{
    protected string $view = 'filament.pages.analytics';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Аналитика';

    protected static ?string $title = 'Аналитика';

    protected static string|\UnitEnum|null $navigationGroup = 'Магазин';

    protected static ?int $navigationSort = 5;

    public string $period = '7d';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() === true && ! auth()->user()->is_blocked;
    }

    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);
        $this->validate(['period' => ['required', 'in:today,7d,30d']]);

        return ['summary' => app(AnalyticsSummary::class)->forPeriod($this->period)];
    }
}
