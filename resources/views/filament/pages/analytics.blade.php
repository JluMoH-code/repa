<x-filament-panels::page>
    <div class="rp-workspace" wire:loading.class="opacity-60">
        <label for="analytics-period" class="block text-sm font-medium">Период</label>
        <select id="analytics-period" wire:model.live="period" class="rp-input mt-2">
            <option value="today">Сегодня</option>
            <option value="7d">Последние 7 дней</option>
            <option value="30d">Последние 30 дней</option>
        </select>
        <p class="mt-4 text-sm text-gray-600">Онлайн-продажи — доставленные заказы по дате оформления. Офлайн-продажи — записи склада по дате продажи. Средний чек относится к онлайн-заказам.</p>
        <div class="rp-metrics mt-6">
            @foreach (['revenue' => 'Все продажи', 'online_revenue' => 'Онлайн', 'offline_revenue' => 'Офлайн', 'average_order' => 'Средний онлайн-чек'] as $key => $label)
                <div>
                    <p class="text-sm text-gray-600">{{ $label }}</p>
                    <strong>{{ number_format($summary[$key] / 100, 2, ',', ' ') }} ₽</strong>
                </div>
            @endforeach
        </div>
        <div class="rp-metrics mt-4">
            @foreach (['orders_count' => 'Доставленные заказы', 'visitors' => 'Посещения (сессии)', 'page_views' => 'Просмотры страниц', 'cart_adds' => 'Добавления в корзину'] as $key => $label)
                <div>
                    <p class="text-sm text-gray-600">{{ $label }}</p>
                    <strong>{{ number_format($summary[$key], 0, ',', ' ') }}</strong>
                </div>
            @endforeach
        </div>
        <p class="mt-6 text-sm text-gray-600">Конверсия в оформление заказа: <strong>{{ $summary['conversion'] === null ? 'Нет данных' : number_format($summary['conversion'], 2, ',', ' ').' %' }}</strong>. Доля сессий с успешным оформлением заказа. Сессии не являются количеством людей. История посещений начинается с подключения аналитики; администраторы и браузеры с DNT не учитываются.</p>
        <div class="rp-two-columns mt-6">
            <section class="rp-panel">
                <h2 class="text-lg font-semibold">Популярные товары онлайн</h2>
                <p class="mt-1 text-sm text-gray-600">Упаковки в доставленных заказах.</p>
                <ul class="mt-4 space-y-3">
                    @forelse ($summary['top_products'] as $product)
                        <li class="flex items-start justify-between gap-4"><span class="min-w-0 break-words">{{ $product['name'] }}</span><strong class="shrink-0">{{ $product['quantity'] }} шт.</strong></li>
                    @empty
                        <li class="text-sm text-gray-500">За период нет доставленных заказов.</li>
                    @endforelse
                </ul>
            </section>
            <section class="rp-panel">
                <h2 class="text-lg font-semibold">Источники посещений</h2>
                <p class="mt-1 text-sm text-gray-600">Первый источник сессии: метка кампании или домен перехода.</p>
                <ul class="mt-4 space-y-3">
                    @forelse ($summary['sources'] as $source)
                        <li class="flex justify-between gap-4"><span class="min-w-0 break-words">{{ $source['name'] === 'direct' ? 'Прямые переходы' : $source['name'] }}</span><strong class="shrink-0">{{ $source['visitors'] }}</strong></li>
                    @empty
                        <li class="text-sm text-gray-500">Посещений пока нет.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</x-filament-panels::page>
