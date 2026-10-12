<x-filament-panels::page>
    @php
        $overview = $this->overview();
        $tasks = $this->tasks();
        $money = fn ($value) => number_format($value / 100, 2, ',', ' ').' ₽';
    @endphp
    <div class="rp-workspace">
        <div class="rp-page-intro">
            <p>Продажи, задачи и состояние магазина в одном месте.</p>
            <label class="rp-inline-field">
                <span>Период</span>
                <select wire:model.live="period" class="rp-input">
                    <option value="today">Сегодня</option>
                    <option value="7d">За 7 дней</option>
                    <option value="30d">За 30 дней</option>
                </select>
            </label>
        </div>
        <div class="rp-metrics" wire:loading.class="opacity-50">
            <div><span>Продажи</span><strong>{{ $money($overview['revenue']) }}</strong><small>Сайт и офлайн-магазин</small></div>
            <div><span>Заказы</span><strong>{{ $overview['orders_count'] }}</strong><small>Завершённые продажи</small></div>
            <div><span>Средний чек сайта</span><strong>{{ $money($overview['average_order']) }}</strong><small>За выбранный период</small></div>
            <div><span>Конверсия сайта</span><strong>{{ $overview['conversion'] === null ? 'Нет данных' : number_format($overview['conversion'], 1, ',', ' ').' %' }}</strong><small>Посещения → заказы</small></div>
        </div>
        <div class="rp-action-bar">
            <a class="rp-button rp-button-primary" href="{{ \App\Filament\Pages\Inventory::getUrl(['operation' => 'offline_sale']) }}">Офлайн-продажа</a>
            <a class="rp-button" href="{{ \App\Filament\Pages\Inventory::getUrl(['operation' => 'receive']) }}">Принять товар</a>
            <a class="rp-button" href="{{ \App\Filament\Resources\Products\ProductResource::getUrl('create') }}">Добавить товар</a>
        </div>
        <section class="rp-panel">
            <h2>Требует внимания</h2>
            <div class="rp-task-row"><div><strong>Новые заказы</strong><p>{{ $tasks['new_orders'] ? 'Ожидают обработки: '.$tasks['new_orders'] : 'Все новые заказы обработаны' }}</p></div><a class="rp-button" href="{{ \App\Filament\Pages\Orders::getUrl(['statusFilter' => 'new']) }}">Открыть заказы</a></div>
            <div class="rp-task-row"><div><strong>Остатки товаров</strong><p>{{ $tasks['low_stock'] ? 'Заканчиваются или закончились: '.$tasks['low_stock'] : 'Нет товаров с низким учтённым остатком' }}</p></div><a class="rp-button" href="{{ \App\Filament\Pages\Inventory::getUrl() }}">Проверить остатки</a></div>
            <div class="rp-task-row"><div><strong>Черновики товаров</strong><p>{{ $tasks['drafts'] ? 'Готовятся к публикации: '.$tasks['drafts'] : 'Все товары готовы' }}</p></div><a class="rp-button" href="{{ \App\Filament\Resources\Products\ProductResource::getUrl('index') }}">Открыть товары</a></div>
        </section>
        <div class="rp-two-columns">
            <section class="rp-panel"><h2>Продажи по каналам</h2><div class="rp-stat-row"><span>Интернет-магазин</span><strong>{{ $money($overview['online_revenue']) }}</strong></div><div class="rp-stat-row"><span>Офлайн-магазин</span><strong>{{ $money($overview['offline_revenue']) }}</strong></div><p class="rp-help">Продажи сайта учитываются после завершения заказа.</p></section>
            <section class="rp-panel"><h2>Посетители сайта</h2><div class="rp-stat-row"><span>Посещения</span><strong>{{ $overview['visitors'] }}</strong></div><div class="rp-stat-row"><span>Добавления в корзину</span><strong>{{ $overview['cart_adds'] }}</strong></div><a class="rp-text-link" href="{{ \App\Filament\Pages\Analytics::getUrl() }}">Открыть аналитику →</a></section>
        </div>
    </div>
</x-filament-panels::page>
