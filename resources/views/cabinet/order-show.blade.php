<x-layouts.shop :footer-categories="$footerCategories" :title="'Заказ ' . $order->number . ' — ' . config('app.name')">
    @php
        $color = $order->status->getColor();
        $badgeClass = match ($color) {
            'success' => 'bg-emerald-100 text-emerald-700',
            'warning' => 'bg-amber-100 text-amber-700',
            'info' => 'bg-sky-100 text-sky-700',
            'danger' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-700',
        };
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-6">
        <h1 class="text-2xl font-bold text-slate-900">Личный кабинет</h1>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[260px_1fr]">
            <x-cabinet.sidebar active="orders" />

            <div class="space-y-6">
                <div>
                    <a href="{{ route('cabinet.orders') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                        Все заказы
                    </a>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="font-mono text-xl font-bold text-slate-900">{{ $order->number }}</h2>
                    <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                        {{ $order->status->getLabel() }}
                    </span>
                    <span class="text-sm text-slate-500">
                        от {{ $order->placed_at->format('d.m.Y H:i') }}
                    </span>
                </div>

                @if (session('status'))
                    <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif
                @error('order')
                    <div class="rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        {{ $message }}
                    </div>
                @enderror

                {{-- Отмена/редактирование доступны до отправки заказа (New/Processing/Paid) --}}
                @if (in_array($order->status, [\App\Enums\OrderStatus::New, \App\Enums\OrderStatus::Processing, \App\Enums\OrderStatus::Paid], true))
                    <div class="flex flex-wrap items-center gap-3">
                        <a
                            href="{{ route('cabinet.orders.edit', $order) }}"
                            class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition-colors hover:border-brand-400 hover:text-brand-700"
                        >
                            Редактировать
                        </a>
                        <form
                            method="POST"
                            action="{{ route('cabinet.orders.cancel', $order) }}"
                            onsubmit="return confirm('Отменить заказ {{ $order->number }}?');"
                        >
                            @csrf
                            <button
                                type="submit"
                                class="rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-600 transition-colors hover:bg-red-100"
                            >
                                Отменить заказ
                            </button>
                        </form>
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Получатель</h3>
                        <div class="mt-2 text-sm">
                            <div class="font-medium text-slate-800">{{ $order->customer_name }}</div>
                            <div class="mt-0.5 text-slate-500">{{ $order->customer_email }}</div>
                            <div class="mt-0.5 text-slate-500">{{ $order->customer_phone }}</div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Адрес доставки</h3>
                        <div class="mt-2 text-sm text-slate-700">
                            <div>{{ $order->delivery_city }}</div>
                            @if ($order->delivery_postcode)
                                <div class="text-slate-500">индекс {{ $order->delivery_postcode }}</div>
                            @endif
                            <div class="mt-0.5 text-slate-500">{{ $order->delivery_address }}</div>
                        </div>
                    </section>
                </div>

                @if ($order->comment)
                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Комментарий</h3>
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $order->comment }}</p>
                    </section>
                @endif

                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Состав заказа
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                                <th class="px-5 py-2 font-medium">Товар</th>
                                <th class="px-5 py-2 font-medium">Цена</th>
                                <th class="px-5 py-2 font-medium">Кол-во</th>
                                <th class="px-5 py-2 text-right font-medium">Сумма</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr class="border-b border-slate-100 last:border-0">
                                    <td class="px-5 py-3 text-slate-800">
                                        <a href="{{ route('products.show', $item->product) }}" class="hover:text-brand-700">
                                            {{ $item->product_name }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-slate-600">
                                        {{ number_format($item->price / 100, 0, ',', ' ') }} ₽
                                    </td>
                                    <td class="px-5 py-3 text-slate-600">{{ $item->quantity }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right font-semibold text-slate-900">
                                        {{ number_format($item->line_total / 100, 0, ',', ' ') }} ₽
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="flex items-center justify-between border-t border-slate-100 px-5 py-3 text-sm">
                        <span class="font-semibold text-slate-500">Итого</span>
                        <span class="text-lg font-bold text-accent-600">
                            {{ number_format($order->total / 100, 0, ',', ' ') }} ₽
                        </span>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-layouts.shop>
