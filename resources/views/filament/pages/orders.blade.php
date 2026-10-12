<x-filament-panels::page>
    @php
        $orders = $this->orders();
        $counts = $this->statusCounts();
    @endphp
    <div class="rp-workspace rp-orders">
        <div class="rp-page-intro"><p>Новые заказы ждут подтверждения. Выберите очередь и откройте заказ для обработки.</p></div>
        <nav class="rp-order-filters" aria-label="Очереди заказов">
            <button type="button" wire:click="$set('statusFilter', '')" class="rp-order-chip {{ $statusFilter === '' ? 'is-active' : '' }}" aria-pressed="{{ $statusFilter === '' ? 'true' : 'false' }}">Все <span>{{ array_sum($counts) }}</span></button>
            @foreach ($this->getStatuses() as $status)
                <button type="button" wire:click="$set('statusFilter', '{{ $status->value }}')" class="rp-order-chip {{ $statusFilter === $status->value ? 'is-active' : '' }}" aria-pressed="{{ $statusFilter === $status->value ? 'true' : 'false' }}">{{ $status->getLabel() }} <span>{{ $counts[$status->value] ?? 0 }}</span></button>
            @endforeach
        </nav>
        <div class="rp-order-search">
            <label for="order-search">Поиск заказа</label>
            <input id="order-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Номер, имя или email покупателя" class="rp-input">
            <span class="rp-help">Найдено: {{ $orders->total() }}</span>
        </div>
        <div class="rp-order-table-wrap" wire:loading.class="opacity-60">
            <table class="rp-order-table">
                <thead><tr><th>Заказ</th><th>Покупатель</th><th>Сумма</th><th>Статус</th><th>Обработка</th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}">
                            <td><a class="rp-order-number" href="{{ \App\Filament\Pages\OrderShow::getUrl(['order' => $order->number]) }}">{{ $order->number }}</a><span class="rp-order-secondary">{{ $order->placed_at->format('d.m.Y · H:i') }}</span><span class="rp-order-secondary">{{ $order->items_count }} позиций</span></td>
                            <td><strong class="rp-order-customer">{{ $order->customer_name }}</strong><a class="rp-order-secondary" href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a><span class="rp-order-secondary">{{ $order->customer_email }}</span></td>
                            <td class="rp-order-amount">{{ number_format($order->total / 100, 2, ',', ' ') }} ₽</td>
                            <td><span class="rp-order-status rp-order-status-{{ $order->status->value }}">{{ $order->status->getLabel() }}</span></td>
                            <td class="rp-order-actions">
                                @unless ($order->status->isFinal())
                                    <label class="sr-only" for="order-status-{{ $order->id }}">Изменить статус заказа {{ $order->number }}</label>
                                    <select id="order-status-{{ $order->id }}" wire:change="changeStatus({{ $order->id }}, $event.target.value)" class="rp-input" wire:loading.attr="disabled" wire:target="changeStatus">
                                        @foreach ($this->availableStatuses($order) as $status)
                                            <option value="{{ $status->value }}" @selected($order->status === $status)>{{ $status->getLabel() }}</option>
                                        @endforeach
                                    </select>
                                @endunless
                                <a class="rp-text-link" href="{{ \App\Filament\Pages\OrderShow::getUrl(['order' => $order->number]) }}">Открыть заказ →</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="rp-order-empty">В этой очереди заказов пока нет. Выберите другой статус или измените поиск.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6"><x-filament::pagination :paginator="$orders" /></div>
    </div>
</x-filament-panels::page>
