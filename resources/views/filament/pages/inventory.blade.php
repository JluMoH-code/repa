<x-filament-panels::page>
    <div class="rp-workspace rp-stock-workspace">
        <div class="rp-stock-intro">
            <div><p class="rp-eyebrow">Один склад для сайта и магазина</p><h2 class="rp-section-title">Всё, что есть на полке</h2><p class="rp-muted">Продали в магазине — запишите продажу. Привезли семена — добавьте поступление.</p></div>
            <p class="rp-stock-unit">Учёт в упаковках<br><span class="rp-muted">по каждой фасовке</span></p>
        </div>

        <div class="rp-stock-toolbar" aria-label="Операции со складом">
            <button type="button" class="rp-stock-action" wire:click="open('offline_sale')"><x-filament::icon icon="heroicon-o-shopping-bag" class="size-6"/><span><strong>Продажа в магазине</strong><small>Списать проданные упаковки</small></span><x-filament::icon icon="heroicon-o-arrow-up-right" class="size-4"/></button>
            <button type="button" class="rp-stock-action" wire:click="open('receive')"><x-filament::icon icon="heroicon-o-arrow-down-tray" class="size-6"/><span><strong>Поступление</strong><small>Добавить новую поставку</small></span><x-filament::icon icon="heroicon-o-arrow-up-right" class="size-4"/></button>
            <button type="button" class="rp-stock-action" wire:click="open('correct')"><x-filament::icon icon="heroicon-o-adjustments-horizontal" class="size-6"/><span><strong>Пересчёт</strong><small>Указать фактический остаток</small></span><x-filament::icon icon="heroicon-o-arrow-up-right" class="size-4"/></button>
        </div>

        <div class="rp-stock-summary" aria-label="Состояние склада">
            <div class="rp-stock-summary-item"><span class="rp-muted">Свободно для продажи</span><strong class="rp-stock-summary-value">{{ number_format($available, 0, ',', ' ') }} <small>уп.</small></strong><span class="rp-muted">в товарах с учётом остатков</span></div>
            <div class="rp-stock-summary-item"><span class="rp-muted">В заказах покупателей</span><strong class="rp-stock-summary-value">{{ number_format($reserved, 0, ',', ' ') }} <small>уп.</small></strong><span class="rp-muted">резерв до выдачи заказа</span></div>
            <button type="button" class="rp-stock-summary-item" wire:click="setFilter('low')"><span class="rp-muted">Нужно пополнить</span><strong class="rp-stock-summary-value">{{ $counts['low'] }} <small>поз.</small></strong><span class="rp-muted">5 упаковок или меньше →</span></button>
            <button type="button" class="rp-stock-summary-item" wire:click="setFilter('unknown')"><span class="rp-muted">Ещё без учёта</span><strong class="rp-stock-summary-value">{{ $counts['unknown'] }} <small>поз.</small></strong><span class="rp-muted">можно начать с пересчёта →</span></button>
        </div>

        @if ($counts['unknown'] > 0)
            <div class="rp-stock-onboarding"><x-filament::icon icon="heroicon-o-light-bulb" class="size-5"/><div><strong>Начните с того, что уже лежит на полке</strong><p>У {{ $counts['unknown'] }} позиций остаток ещё не задан. Пересчитайте упаковки и укажите число — наличие на сайте станет обновляться автоматически. До этого действует прежнее наличие.</p></div><button type="button" class="rp-text-link" wire:click="setFilter('unknown')">Показать без учёта →</button></div>
        @endif

        <div class="rp-stock-filters">
            <div class="rp-stock-filter-tabs" aria-label="Показать остатки">
                @foreach (['all' => 'Все', 'low' => 'Мало', 'empty' => 'Закончились', 'unknown' => 'Без учёта'] as $key => $label)
                    <button type="button" class="rp-stock-filter" aria-pressed="{{ $filter === $key ? 'true' : 'false' }}" wire:click="setFilter('{{ $key }}')">{{ $label }} <span>{{ $counts[$key] }}</span></button>
                @endforeach
            </div>
            <label class="rp-stock-search"><x-filament::icon icon="heroicon-o-magnifying-glass" class="size-5"/><input wire:model.live.debounce.300ms="search" type="search" placeholder="Название или артикул" aria-label="Найти товар по названию или артикулу"></label>
        </div>

        <div class="rp-stock-list" wire:loading.class="opacity-60" wire:target="search,setFilter">
            <div class="rp-stock-list-head" aria-hidden="true"><span>Товар и фасовка</span><span>На полке / резерв / свободно</span><span>Наличие</span><span></span></div>
            @forelse ($rows as $row)
                @php
                    $product = $row['product'];
                    $stock = $row['stock'];
                    $variantId = $stock instanceof \App\Models\ProductVariant ? $stock->id : null;
                    $image = $product->images->firstWhere('is_main', true) ?? $product->images->first();
                    $free = $stock->availableQuantity();
                    $state = $free === null ? 'unknown' : ($free === 0 ? 'empty' : ($free <= 5 ? 'low' : 'ready'));
                @endphp
                <div class="rp-stock-row" wire:key="stock-{{ $product->id }}-{{ $variantId ?? 'base' }}" data-stock-position="{{ $product->id }}:{{ $variantId ?? 'base' }}">
                    <div class="rp-stock-product">
                        <div class="rp-stock-avatar">@if ($image)<img src="{{ Storage::disk('public')->url($image->path) }}" alt="" loading="lazy">@else<x-filament::icon icon="heroicon-o-cube" class="size-6"/>@endif</div>
                        <div><a class="rp-stock-name" href="{{ \App\Filament\Resources\Products\ProductResource::getUrl('edit', ['record' => $product]) }}">{{ $product->name }}</a><p class="rp-stock-meta">{{ $variantId ? $stock->label : ($product->package_size ?: $product->unit) }} @if ($product->sku)<span>· {{ $product->sku }}</span>@endif</p></div>
                    </div>
                    <div class="rp-stock-numbers">
                        <div class="rp-stock-number"><span>На полке</span><strong>{{ $stock->stock_quantity ?? '—' }}</strong></div>
                        <div class="rp-stock-number"><span>В резерве</span><strong>{{ $stock->reserved_quantity }}</strong></div>
                        <div class="rp-stock-number is-available"><span>Свободно</span><strong>{{ $free ?? '—' }}</strong></div>
                    </div>
                    <div><span class="rp-stock-badge is-{{ $state }}">{{ ['ready' => 'В наличии', 'low' => 'Мало', 'empty' => 'Закончились', 'unknown' => 'Без учёта'][$state] }}</span>@if ($state === 'unknown')<small class="rp-stock-legacy">{{ $stock->in_stock ? 'На сайте: в наличии' : 'На сайте: нет в наличии' }}</small>@endif</div>
                    <button type="button" class="rp-button rp-stock-manage" wire:click="manage({{ $product->id }}, {{ $variantId ?? 'null' }})" aria-label="{{ $state === 'unknown' ? 'Начать учёт' : 'Управлять остатком' }}: {{ $product->name }} {{ $variantId ? $stock->label : '' }}">{{ $state === 'unknown' ? 'Начать учёт' : 'Управлять' }}<x-filament::icon icon="heroicon-o-chevron-right" class="size-4"/></button>
                </div>
            @empty
                <div class="rp-empty"><x-filament::icon icon="heroicon-o-magnifying-glass" class="size-8"/><h3>Здесь пока нет товаров</h3><p>Измените поиск или выберите другой фильтр.</p><button type="button" class="rp-text-link" wire:click="setFilter('all')">Показать все товары</button></div>
            @endforelse
        </div>
        <div class="rp-stock-pagination"><x-filament::pagination :paginator="$products" /></div>

        <details class="rp-stock-history">
            <summary><span><x-filament::icon icon="heroicon-o-clock" class="size-5"/><strong>История склада</strong><small>Последние 30 операций · кто и что изменил</small></span><x-filament::icon icon="heroicon-o-chevron-down" class="size-4"/></summary>
            <div class="rp-table-wrap overflow-x-auto"><table class="rp-table"><thead><tr><th>Когда и что</th><th>Операция</th><th>Упаковки</th><th>Сотрудник</th></tr></thead><tbody>
                @forelse ($movements as $movement)
                    <tr><td><span class="rp-muted">{{ $movement->created_at->format('d.m.Y H:i') }}</span><br><strong>{{ $movement->product->name }}</strong> {{ $movement->variant?->label }}@if ($movement->note)<p class="rp-muted">{{ $movement->note }}</p>@endif</td><td>{{ ['receipt' => 'Поступление', 'correction' => 'Пересчёт', 'offline_sale' => 'Продажа в магазине', 'reservation' => 'Резерв заказа', 'release' => 'Снятие резерва', 'order_sale' => 'Выдача заказа'][$movement->type] ?? $movement->type }}</td><td>{{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}<br>@if ($movement->reserved_change)<small class="rp-muted">Резерв {{ $movement->reserved_change > 0 ? '+' : '' }}{{ $movement->reserved_change }}</small>@endif</td><td>{{ $movement->user?->name ?? 'Покупатель' }}</td></tr>
                @empty <tr><td colspan="4">Движений пока нет. Первая запись появится после поступления, пересчёта или продажи.</td></tr> @endforelse
            </tbody></table></div>
        </details>
    </div>

    <x-filament::modal id="inventory-operation" width="xl" x-init="if ($wire.modalOpen) open()" x-on:modal-closed.window="if ($event.detail.id === 'inventory-operation' && $wire.modalOpen) $wire.close()">
        <x-slot name="heading">{{ ['receive' => 'Добавить поступление', 'correct' => 'Указать фактический остаток', 'offline_sale' => 'Записать продажу'][$operation] ?? 'Управление остатком' }}</x-slot>
        <form wire:submit="save" class="rp-form-grid">
            <div class="rp-stock-modal-tabs" aria-label="Тип операции">
                @foreach (['receive' => 'Поступление', 'offline_sale' => 'Продажа', 'correct' => 'Пересчёт'] as $key => $label)<button type="button" aria-pressed="{{ $operation === $key ? 'true' : 'false' }}" wire:click="switchOperation('{{ $key }}')">{{ $label }}</button>@endforeach
            </div>
            <label class="rp-field">Найти товар <input type="search" wire:model.live.debounce.300ms="modalProductSearch" class="rp-input" placeholder="Название или артикул"><small class="rp-muted">Найдите товар по названию или артикулу. Показываем до 30 вариантов.</small></label>
            <label class="rp-field">Товар <select wire:model.live="productId" class="rp-input"><option value="">Выберите товар из результатов</option>@foreach ($options as $option)<option value="{{ $option->id }}">{{ $option->name }}{{ $option->sku ? ' · '.$option->sku : '' }}</option>@endforeach</select></label>
            @if ($options->isEmpty())<p class="rp-muted">Товар не найден. Попробуйте другое название или артикул.</p>@endif
            @error('productId')<p class="rp-error">{{ $message }}</p>@enderror
            @if ($variants->isNotEmpty())<label class="rp-field">Фасовка <select wire:model.live="variantId" class="rp-input">@foreach ($variants as $variant)<option value="{{ $variant->id }}">{{ $variant->label }}</option>@endforeach</select></label>@endif
            @if ($selectedStock)
                <div class="rp-stock-modal-context"><span>Сейчас на полке <strong>{{ $selectedStock->stock_quantity ?? 'не задано' }}</strong></span><span>В резерве <strong>{{ $selectedStock->reserved_quantity }}</strong></span><span>Можно продать <strong>{{ $selectedStock->availableQuantity() ?? 'не задано' }}</strong></span></div>
                @if ($selectedStock->stock_quantity === null)<p class="rp-muted">Сначала пересчитайте упаковки на полке и введите количество. Это включит учёт остатков для этой фасовки.</p>@endif
            @endif
            <label class="rp-field">{{ ['correct' => 'Сколько упаковок сейчас на полке?', 'receive' => 'Сколько упаковок привезли?', 'offline_sale' => 'Сколько упаковок продали?'][$operation] ?? 'Количество упаковок' }}<input type="number" wire:model.live.debounce.250ms="quantity" min="{{ $operation === 'correct' ? 0 : 1 }}" step="1" class="rp-input" placeholder="0" required autofocus>@if ($operation === 'correct')<small class="rp-muted">Полный остаток, включая упаковки в резерве заказов.</small>@endif</label>
            @error('quantity')<p class="rp-error">{{ $message }}</p>@enderror
            @if ($operation === 'offline_sale')<label class="rp-field">Цена за упаковку, ₽ <input type="text" inputmode="decimal" class="rp-input" wire:model="unitPrice" placeholder="125,00" required><small class="rp-muted">Резерв заказов защищён: его нельзя продать в магазине.</small></label>@error('unitPrice')<p class="rp-error">{{ $message }}</p>@enderror @endif
            @if ($preview !== null)<div class="rp-stock-preview"><span>После операции на полке</span><strong>{{ $preview }} <small>уп.</small></strong>@if ($selectedStock && $preview < $selectedStock->reserved_quantity)<p class="rp-error">Остаток не может быть меньше резерва заказов.</p>@endif</div>@endif
            <details><summary class="rp-text-link">Добавить примечание</summary><label class="rp-field">Примечание <textarea wire:model="note" class="rp-input" maxlength="2000" rows="2" placeholder="Например, поставка от производителя"></textarea></label></details>@error('note')<p class="rp-error">{{ $message }}</p>@enderror
            <div class="rp-actions"><x-filament::button type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ ['receive' => 'Добавить на склад', 'correct' => 'Сохранить остаток', 'offline_sale' => 'Записать продажу'][$operation] ?? 'Сохранить' }}</span><span wire:loading wire:target="save">Сохраняем…</span></x-filament::button><x-filament::button color="gray" wire:click="close">Отмена</x-filament::button></div>
        </form>
    </x-filament::modal>
</x-filament-panels::page>
