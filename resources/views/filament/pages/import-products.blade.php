<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section
            icon="heroicon-o-arrow-up-tray"
            icon-color="primary"
            heading="Загрузка файла"
            description="CSV с колонками: Артикул, Штрихкод, Название, Цена, Категория, Производитель. Цена указывается в рублях (например, 150.00). Разделитель — запятая или точка с запятой."
        >
            <form wire:submit.prevent="runPreview" class="flex flex-col gap-4 md:flex-row md:items-end">
                <div class="flex-1">
                    <input type="file" wire:model="csvFile" accept=".csv,.txt"
                        class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-primary-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-primary-500" />

                    @error('csvFile')
                        <p class="mt-2 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="whitespace-nowrap rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700"
                    wire:loading.attr="disabled" wire:target="csvFile,runPreview">
                    Показать предпросмотр
                </button>
            </form>
        </x-filament::section>

        @if ($report)
            <x-filament::section
                icon="heroicon-o-check-circle"
                icon-color="success"
                heading="Импорт завершён"
            >
                <p class="text-sm text-success-700">
                    Создано: <strong>{{ $report['created'] }}</strong>,
                    обновлено: <strong>{{ $report['updated'] }}</strong>,
                    пропущено с ошибками: <strong>{{ $report['skipped'] }}</strong>.
                </p>
            </x-filament::section>
        @endif

        @if ($previewReady)
            @php
                $toCreate = collect($preview)->where('action', 'create')->count();
                $toUpdate = collect($preview)->where('action', 'update')->count();
                $errors = collect($preview)->where('action', 'error')->count();
            @endphp

            <x-filament::section
                icon="heroicon-o-table-cells"
                icon-color="primary"
                heading="Предпросмотр"
            >
                <x-slot name="afterHeader">
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ count($preview) }} строк —
                        <span class="font-medium text-success-600">{{ $toCreate }} новых</span>,
                        <span class="font-medium text-info-600">{{ $toUpdate }} обновится</span>,
                        <span class="font-medium text-danger-600">{{ $errors }} с ошибками</span>
                    </span>
                </x-slot>

                <div class="max-h-96 overflow-x-auto overflow-y-auto rounded-lg border">
                    <table class="min-w-full text-sm">
                        <thead class="sticky top-0 bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-3 py-2 text-left">#</th>
                                <th class="px-3 py-2 text-left">Артикул</th>
                                <th class="px-3 py-2 text-left">Название</th>
                                <th class="px-3 py-2 text-left">Цена</th>
                                <th class="px-3 py-2 text-left">Категория</th>
                                <th class="px-3 py-2 text-left">Статус</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($preview as $row)
                                <tr>
                                    <td class="px-3 py-2 text-gray-400">{{ $row['line'] }}</td>
                                    <td class="px-3 py-2 font-mono">{{ $row['sku'] }}</td>
                                    <td class="px-3 py-2">{{ $row['name'] }}</td>
                                    <td class="px-3 py-2">{{ $row['price_raw'] }}</td>
                                    <td class="px-3 py-2">{{ $row['category'] }}</td>
                                    <td class="px-3 py-2">
                                        @if ($row['action'] === 'create')
                                            <span class="inline-flex rounded-full bg-success-100 px-2 py-0.5 text-xs font-medium text-success-800">Новый</span>
                                        @elseif ($row['action'] === 'update')
                                            <span class="inline-flex rounded-full bg-info-100 px-2 py-0.5 text-xs font-medium text-info-800">Обновится</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-danger-100 px-2 py-0.5 text-xs font-medium text-danger-800"
                                                title="{{ implode('; ', $row['errors']) }}">
                                                Ошибка: {{ implode('; ', $row['errors']) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <x-filament::button
                        type="button"
                        wire:click="applyImport"
                        wire:loading.attr="disabled"
                        wire:target="applyImport"
                        @if ($toCreate + $toUpdate === 0) disabled @endif
                    >
                        Применить импорт ({{ $toCreate + $toUpdate }} строк)
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
