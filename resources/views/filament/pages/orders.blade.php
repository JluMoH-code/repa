<x-filament-panels::page>
    @php $orders = $this->orders(); @endphp

    <div class="space-y-6">
        <x-filament::section icon="heroicon-o-shopping-bag" icon-color="primary" heading="Заказы">
            <div class="flex flex-col gap-4 md:flex-row md:items-end">
                <div class="flex-1">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Поиск: номер, email, имя..."
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                    >
                </div>

                <div class="md:w-56">
                    <select
                        wire:model.live="statusFilter"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                    >
                        <option value="">Все статусы</option>
                        @foreach ($this->getStatuses() as $status)
                            <option value="{{ $status->value }}">{{ $status->getLabel() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto rounded-lg border">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <th class="px-3 py-2 font-medium">Номер</th>
                            <th class="px-3 py-2 font-medium">Покупатель</th>
                            <th class="px-3 py-2 font-medium">Телефон</th>
                            <th class="px-3 py-2 font-medium">Дата</th>
                            <th class="px-3 py-2 font-medium">Сумма</th>
                            <th class="px-3 py-2 font-medium">Статус</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($orders as $order)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-3 py-2">
                                    <a href="{{ \App\Filament\Pages\OrderShow::getUrl(['order' => $order->number]) }}"
                                        class="font-mono font-semibold text-primary-600 hover:underline">
                                        {{ $order->number }}
                                    </a>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-800 dark:text-gray-200">{{ $order->customer_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $order->customer_email }}</div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-gray-600 dark:text-gray-300">{{ $order->customer_phone }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-gray-600 dark:text-gray-300">
                                    {{ $order->placed_at->format('d.m.Y H:i') }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 font-semibold text-gray-800 dark:text-gray-200">
                                    {{ number_format($order->total / 100, 0, ',', ' ') }} ₽
                                </td>
                                <td class="px-3 py-2">
                                    <select
                                        wire:change="changeStatus({{ $order->id }}, $event.target.value)"
                                        class="rounded-md border border-gray-300 px-2 py-1 text-xs focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                    >
                                        @foreach ($this->getStatuses() as $status)
                                            <option value="{{ $status->value }}" @selected($order->status === $status)>
                                                {{ $status->getLabel() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">
                                    <a href="{{ \App\Filament\Pages\OrderShow::getUrl(['order' => $order->number]) }}"
                                        class="text-xs font-medium text-primary-600 hover:underline">
                                        Открыть →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-10 text-center text-sm text-gray-500">
                                    Заказы не найдены.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
