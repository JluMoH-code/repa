@php
    $badges = [
        ['icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'text' => 'Проверенные поставщики'],
        ['icon' => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 0h-12', 'text' => 'Доставка по всей стране'],
        ['icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z', 'text' => 'Оплата при получении'],
        ['icon' => 'M12 6v12m6-6H6', 'text' => 'Без минимальной суммы заказа'],
    ];
@endphp

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach ($badges as $badge)
        <div class="flex items-center gap-3 rounded-lg bg-brand-50 px-4 py-3">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-white text-brand-600">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $badge['icon'] }}" />
                </svg>
            </span>
            <span class="text-sm font-medium text-slate-700">{{ $badge['text'] }}</span>
        </div>
    @endforeach
</div>
