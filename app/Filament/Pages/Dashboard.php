<?php

namespace App\Filament\Pages;

use App\Actions\Analytics\AnalyticsSummary;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Обзор магазина';

    protected static ?string $navigationLabel = 'Обзор';

    public string $period = '7d';

    public static function canAccess(): bool
    {
        return Auth::user()?->role === UserRole::Admin && ! Auth::user()?->is_blocked;
    }

    public function overview(): array
    {
        abort_unless(static::canAccess(), 403);

        $this->validate(['period' => 'required|in:today,7d,30d']);

        return app(AnalyticsSummary::class)->forPeriod($this->period);
    }

    public function tasks(): array
    {
        abort_unless(static::canAccess(), 403);

        return [
            'drafts' => Product::where('status', ProductStatus::Draft)->count(),
            'new_orders' => Order::where('status', 'new')->count(),
            'low_stock' => Product::whereNotNull('stock_quantity')
                ->doesntHave('variants')
                ->whereRaw('stock_quantity - reserved_quantity <= ?', [5])->count()
                + ProductVariant::whereNotNull('stock_quantity')
                    ->whereRaw('stock_quantity - reserved_quantity <= ?', [5])->count(),
        ];
    }
}
