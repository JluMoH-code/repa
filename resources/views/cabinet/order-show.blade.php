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

                        <div x-data="{ showCancelModal: false }">
                            <button
                                type="button"
                                @click="showCancelModal = true"
                                class="rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-600 transition-colors hover:bg-red-100"
                            >
                                Отменить заказ
                            </button>

                            {{-- Модалка подтверждения отмены --}}
                            <div
                                x-show="showCancelModal"
                                x-cloak
                                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                role="dialog"
                                aria-modal="true"
                                @keydown.escape.window="showCancelModal = false"
                            >
                                {{-- Затемнённый фон с лёгким блюром --}}
                                <div
                                    x-show="showCancelModal"
                                    x-transition.opacity.duration.150ms
                                    class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                                    @click="showCancelModal = false"
                                ></div>

                                {{-- Карточка --}}
                                <div
                                    x-show="showCancelModal"
                                    x-transition.opacity.duration.150ms
                                    class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
                                >
                                    <div class="flex items-start gap-4">
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                            </svg>
                                        </span>
                                        <div>
                                            <h3 class="text-base font-semibold text-slate-900">
                                                Отменить заказ {{ $order->number }}?
                                            </h3>
                                            <p class="mt-1 text-sm text-slate-500">
                                                Заказ будет переведён в статус «Отменён». Это действие нельзя отменить.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-5 flex justify-end gap-3">
                                        <button
                                            type="button"
                                            @click="showCancelModal = false"
                                            class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition-colors hover:border-brand-400 hover:text-brand-700"
                                        >
                                            Не отменять
                                        </button>
                                        <form method="POST" action="{{ route('cabinet.orders.cancel', $order) }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-red-700"
                                            >
                                                Да, отменить
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Получение заказа</h3>
                        @php
                            $isPickup = ($order->delivery_method ?? \App\Enums\OrderDeliveryMethod::Pickup) === \App\Enums\OrderDeliveryMethod::Pickup;
                            $shopAddress = app(\App\Actions\Settings\SettingsManager::class)->get('address');
                        @endphp
                        <div class="mt-2 text-sm text-slate-700">
                            @if ($isPickup)
                                <span class="inline-block rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                    Самовывоз
                                </span>
                                <div class="mt-2 text-slate-500">Магазин Repa: {{ $shopAddress }}</div>
                            @else
                                <span class="inline-block rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-700">
                                    Доставка по адресу
                                </span>
                                <div class="mt-2">{{ $order->delivery_city }}</div>
                                @if ($order->delivery_postcode)
                                    <div class="text-slate-500">индекс {{ $order->delivery_postcode }}</div>
                                @endif
                                <div class="mt-0.5 text-slate-500">{{ $order->delivery_address }}</div>
                                @if ($order->delivery_service)
                                    <div class="mt-1 text-xs text-slate-400">Служба: {{ $order->delivery_service }}</div>
                                @endif
                            @endif
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
