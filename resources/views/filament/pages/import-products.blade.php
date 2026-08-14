<x-filament-panels::page>
    <div class="space-y-6">
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold mb-1">Загрузка файла</h2>
            <p class="text-sm text-gray-500 mb-4">
                CSV с колонками: Артикул, Штрихкод, Название, Цена, Категория, Производитель.
                Цена указывается в рублях (например, 150.00). Разделитель — запятая или точка с запятой.
            </p>

            <form wire:submit.prevent="runPreview" class="flex items-center gap-4">
                <input type="file" wire:model="csvFile" accept=".csv,.txt"
                    class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-primary-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-primary-500" />

                <button type="submit"
                    class="whitespace-nowrap rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700"
                    wire:loading.attr="disabled" wire:target="csvFile,runPreview">
                    Показать предпросмотр
                </button>
            </form>

            @error('csvFile')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        @if ($report)
            <div class="fi-section rounded-xl bg-emerald-50 p-6 ring-1 ring-emerald-200">
                <h2 class="text-base font-semibold text-emerald-800 mb-1">Импорт завершён</h2>
                <p class="text-sm text-emerald-700">
                    Создано: <strong>{{ $report['created'] }}</strong>,
                    обновлено: <strong>{{ $report['updated'] }}</strong>,
                    пропущено с ошибками: <strong>{{ $report['skipped'] }}</strong>.
                </p>
            </div>
        @endif

        @if ($previewReady)
            <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                @php
                    $toCreate = collect($preview)->where('action', 'create')->count();
                    $toUpdate = collect($preview)->where('action', 'update')->count();
                    $errors = collect($preview)->where('action', 'error')->count();
                @endphp

                <h2 class="text-base font-semibold mb-3">
                    Предпросмотр: {{ count($preview) }} строк —
                    <span class="text-emerald-600">{{ $toCreate }} новых</span>,
                    <span class="text-blue-600">{{ $toUpdate }} обновится</span>,
                    <span class="text-red-600">{{ $errors }} с ошибками</span>
                </h2>

                <div class="overflow-x-auto max-h-96 overflow-y-auto border rounded-lg">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0">
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
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Новый</span>
                                        @elseif ($row['action'] === 'update')
                                            <span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">Обновится</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800"
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
                    <button type="button" wire:click="applyImport"
                        wire:loading.attr="disabled" wire:target="applyImport"
                        @if($toCreate + $toUpdate === 0) disabled @endif
                        class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        Применить импорт ({{ $toCreate + $toUpdate }} строк)
                    </button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
