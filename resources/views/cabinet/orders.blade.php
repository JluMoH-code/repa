<x-layouts.shop :footer-categories="$footerCategories" :title="'Мои заказы — ' . config('app.name')">
    <div class="mx-auto max-w-7xl px-4 py-6">
        <h1 class="text-2xl font-bold text-slate-900">Личный кабинет</h1>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[260px_1fr]">
            <x-cabinet.sidebar active="orders" />

            <div>
                <h2 class="text-lg font-semibold text-slate-900">Мои заказы</h2>

                @if (session('status'))
                    <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($orders->isEmpty())
                    <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white py-16 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" class="mx-auto size-16 text-slate-300">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.708 2.602-7.201.245-1.007-.44-2.049-1.4-2.049H5.106M7.5 14.25 5.5 5.25M6 18.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        <p class="mt-4 text-lg font-semibold text-slate-700">Заказов пока нет</p>
                        <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                            Оформите первый заказ — на странице корзины нажмите «Оформить заказ».
                        </p>
                        <a href="{{ route('storefront') }}" class="mt-6 inline-block rounded-md bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                            Перейти в каталог
                        </a>
                    </div>
                @else
                    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                                    <th class="px-4 py-3 font-medium">Номер</th>
                                    <th class="px-4 py-3 font-medium">Дата</th>
                                    <th class="px-4 py-3 font-medium">Статус</th>
                                    <th class="px-4 py-3 font-medium">Товаров</th>
                                    <th class="px-4 py-3 text-right font-medium">Сумма</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    @php
                                        $color = $order->status->getColor();
                                        $badgeClass = match ($color) {
                                            'success' => 'bg-emerald-100 text-emerald-700',
                                            'warning' => 'bg-amber-100 text-amber-700',
                                            'info' => 'bg-sky-100 text-sky-700',
                                            'danger' => 'bg-red-100 text-red-700',
                                            default => 'bg-slate-100 text-slate-700',
                                        };
                                        $itemsLabel = \App\Support\RussianPlural::items($order->items_count);
                                    @endphp
                                    <tr class="border-b border-slate-100 last:border-0">
                                        <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-800">
                                            {{ $order->number }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                            {{ $order->placed_at->format('d.m.Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                                {{ $order->status->getLabel() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            {{ $order->items_count }} {{ $itemsLabel }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ number_format($order->total / 100, 0, ',', ' ') }} ₽
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <a href="{{ route('cabinet.orders.show', $order) }}" class="text-sm font-medium text-brand-700 hover:underline">
                                                Подробнее
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.shop>
