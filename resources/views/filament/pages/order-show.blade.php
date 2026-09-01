<x-filament-panels::page>
    @php
        $statusColor = match ($order->status->getColor()) {
            'success' => 'bg-success-100 text-success-800',
            'warning' => 'bg-warning-100 text-warning-800',
            'info' => 'bg-info-100 text-info-800',
            'danger' => 'bg-danger-100 text-danger-800',
            default => 'bg-gray-100 text-gray-800',
        };
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ \App\Filament\Pages\Orders::getUrl() }}"
                    class="text-sm font-medium text-primary-600 hover:underline">
                    ← Все заказы
                </a>
                <h1 class="mt-1 font-mono text-2xl font-bold text-gray-900 dark:text-white">{{ $order->number }}</h1>
            </div>
            <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $statusColor }}">
                {{ $order->status->getLabel() }}
            </span>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <x-filament::section heading="Покупатель">
                <div class="text-sm">
                    <div class="font-medium text-gray-800 dark:text-gray-200">{{ $order->customer_name }}</div>
                    <div class="mt-0.5 text-gray-500">{{ $order->customer_email }}</div>
                    <div class="mt-0.5 text-gray-500">{{ $order->customer_phone }}</div>
                    @if ($order->user)
                        <a href="{{ route('filament.admin.resources.users.edit', $order->user) }}"
                            class="mt-2 inline-block text-xs font-medium text-primary-600 hover:underline">
                            Профиль пользователя →
                        </a>
                    @endif
                </div>
            </x-filament::section>

            <x-filament::section heading="Доставка">
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    <div>{{ $order->delivery_city }}</div>
                    @if ($order->delivery_postcode)
                        <div class="text-gray-500">индекс {{ $order->delivery_postcode }}</div>
                    @endif
                    <div class="mt-0.5">{{ $order->delivery_address }}</div>
                </div>
            </x-filament::section>

            <x-filament::section heading="Статус">
                <form wire:submit.prevent="saveStatus" class="flex items-end gap-2">
                    <div class="flex-1">
                        <select
                            wire:model="newStatus"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                        >
                            @foreach ($this->getStatuses() as $status)
                                <option value="{{ $status->value }}">{{ $status->getLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="saveStatus">
                        Сохранить
                    </x-filament::button>
                </form>
                <p class="mt-2 text-xs text-gray-400">
                    Оформлен {{ $order->placed_at->format('d.m.Y H:i') }}.
                </p>
            </x-filament::section>
        </div>

        @if ($order->comment)
            <x-filament::section heading="Комментарий покупателя">
                <p class="whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $order->comment }}</p>
            </x-filament::section>
        @endif

        <x-filament::section heading="Состав заказа">
            <div class="overflow-x-auto rounded-lg border">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <th class="px-3 py-2 font-medium">Товар</th>
                            <th class="px-3 py-2 font-medium">Цена за ед.</th>
                            <th class="px-3 py-2 font-medium">Кол-во</th>
                            <th class="px-3 py-2 text-right font-medium">Сумма</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="px-3 py-2 text-gray-800 dark:text-gray-200">
                                    {{ $item->product_name }}
                                    @if ($item->product)
                                        <a href="{{ route('filament.admin.resources.products.edit', $item->product) }}"
                                            class="ml-1 text-xs text-primary-600 hover:underline">
                                            (товар →)
                                        </a>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-gray-600 dark:text-gray-300">
                                    {{ number_format($item->price / 100, 0, ',', ' ') }} ₽
                                </td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $item->quantity }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right font-semibold text-gray-800 dark:text-gray-200">
                                    {{ number_format($item->line_total / 100, 0, ',', ' ') }} ₽
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3 flex items-center justify-between border-t border-gray-200 pt-3 dark:border-gray-800">
                <span class="text-sm font-medium text-gray-500">Итого</span>
                <span class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($order->total / 100, 0, ',', ' ') }} ₽
                </span>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
