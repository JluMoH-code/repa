<x-layouts.shop :footer-categories="$footerCategories" :title="'Саунар — ' . config('app.name')">
    <x-shop.breadcrumbs :items="$breadcrumbs" />

    <div class="mx-auto max-w-7xl px-4 pb-12">
        <div class="grid gap-6 lg:grid-cols-[380px_1fr_320px]">
            {{-- Галерея --}}
            <div>
                <x-shop.product-gallery :images="$galleryImages" />
            </div>

            {{-- Информация о товаре --}}
            <div x-data="{ current: 2, total: 8 }">
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-slate-900">Саунар, гербицид сплошного действия</h1>

                    <div class="flex shrink-0 gap-1">
                        <button type="button" class="flex size-8 items-center justify-center rounded-full border border-slate-200 text-slate-400 hover:text-brand-700" aria-label="Предыдущий товар">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                            </svg>
                        </button>
                        <button type="button" class="flex size-8 items-center justify-center rounded-full border border-slate-200 text-slate-400 hover:text-brand-700" aria-label="Следующий товар">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="mt-2 flex items-center gap-3 text-sm text-slate-500">
                    <span>Артикул: 48213</span>
                    <span class="text-slate-300">•</span>
                    <a href="#" class="text-brand-700 hover:underline">Добавить отзыв</a>
                </div>

                <div class="mt-5 rounded-xl border border-slate-200 bg-white p-4">
                    <h2 class="mb-3 text-sm font-semibold text-slate-900">Краткая характеристика:</h2>
                    <dl class="space-y-2 text-sm">
                        @foreach ($characteristics as $item)
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">{{ $item['label'] }}</dt>
                                <dd class="text-right font-medium {{ !empty($item['highlight']) ? 'text-accent-600' : 'text-slate-800' }}">
                                    {{ $item['value'] }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                <a href="#" class="mt-4 inline-block text-sm font-semibold tracking-wide text-accent-600 uppercase hover:text-accent-700">
                    Посмотреть аналоги
                </a>

                <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500">
                                <th class="px-4 py-2 text-left font-medium">Упаковка</th>
                                <th class="px-4 py-2 text-left font-medium">Кол-во</th>
                                <th class="px-4 py-2 text-left font-medium">Наличие</th>
                                <th class="px-4 py-2 text-left font-medium">Цена</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($packaging as $row)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $row['label'] }}</td>
                                    <td class="px-4 py-3"><x-shop.quantity-stepper /></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-800">
                                            В наличии
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-bold text-accent-600">{{ number_format($row['price'], 0, ',', ' ') }} ₽</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-4">
                    <span class="text-3xl font-bold text-accent-600">{{ number_format($packaging[0]['price'], 0, ',', ' ') }} ₽</span>

                    <button type="button" class="rounded-md bg-brand-600 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-700">
                        Купить
                    </button>

                    <button type="button" class="flex size-11 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:text-accent-600" aria-label="В избранное">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                        </svg>
                    </button>

                    <button type="button" class="flex size-11 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:text-brand-700" aria-label="Сравнить">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5M21 16.5 16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Сайдбар: преимущества + похожие товары --}}
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-3">
                    <div class="flex items-center gap-3 rounded-lg bg-slate-100 p-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent-500 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </span>
                        <span class="text-sm font-medium text-slate-700">Только оригинальные товары</span>
                    </div>
                    <div class="flex items-center gap-3 rounded-lg bg-slate-100 p-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent-500 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 0h-12" />
                            </svg>
                        </span>
                        <span class="text-sm font-medium text-slate-700">Быстрая доставка в любую точку России</span>
                    </div>
                    <div class="flex items-center gap-3 rounded-lg bg-slate-100 p-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent-500 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                            </svg>
                        </span>
                        <span class="text-sm font-medium text-slate-700">Семена от проверенных производителей</span>
                    </div>
                    <div class="flex items-center gap-3 rounded-lg bg-slate-100 p-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent-500 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                            </svg>
                        </span>
                        <span class="text-sm font-medium text-slate-700">Большой выбор сортов</span>
                    </div>
                </div>

                <x-shop.similar-products :items="$similarProducts" />
            </div>
        </div>

        <div class="mt-8">
            <x-shop.product-tabs :tabs="$tabs" />
        </div>
    </div>
</x-layouts.shop>
