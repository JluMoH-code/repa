<?php

namespace App\Actions\Analytics;

use App\Enums\OrderStatus;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class AnalyticsSummary
{
    public function forPeriod(string $period = '7d'): array
    {
        if (! in_array($period, ['today', '7d', '30d'], true)) {
            throw new InvalidArgumentException('Неизвестный период аналитики.');
        }
        $end = now(config('app.timezone'));
        $start = $end->copy()->startOfDay()->subDays(match ($period) {
            'today' => 0, '7d' => 6, '30d' => 29
        });
        $orders = Order::query()->where('status', OrderStatus::Delivered)->whereBetween('placed_at', [$start, $end]);
        $online = (int) (clone $orders)->sum('total');
        $count = (clone $orders)->count();
        $offline = 0;
        if (Schema::hasTable('inventory_movements')) {
            $offline = (int) DB::table('inventory_movements')->where('type', 'offline_sale')->whereBetween('created_at', [$start, $end])->selectRaw('COALESCE(SUM(-quantity_change * unit_price), 0) AS revenue')->value('revenue');
        }
        $events = AnalyticsEvent::query()->whereBetween('created_at', [$start, $end]);
        $visitors = (clone $events)->distinct()->count('visitor_token');
        $purchasers = (clone $events)->where('event_type', 'order')->distinct()->count('visitor_token');
        $top = OrderItem::query()->whereIn('order_id', (clone $orders)->select('id'))->select('product_id as id', 'product_name as name')->selectRaw('SUM(quantity) as quantity')->groupBy('product_id', 'product_name')->orderByDesc('quantity')->limit(10)->get()->toArray();

        return [
            'online_revenue' => $online,
            'offline_revenue' => $offline,
            'revenue' => $online + $offline,
            'orders_count' => $count,
            'average_order' => $count ? intdiv($online, $count) : 0,
            'visitors' => $visitors,
            'page_views' => (clone $events)->where('event_type', 'page_view')->count(),
            'cart_adds' => (clone $events)->where('event_type', 'cart_add')->count(),
            'conversion' => $visitors ? round($purchasers / $visitors * 100, 2) : null,
            'top_products' => $top,
            'sources' => (clone $events)->select('source as name')->selectRaw('COUNT(DISTINCT visitor_token) as visitors')->groupBy('source')->orderByDesc('visitors')->get()->toArray(),
        ];
    }
}
