<x-layouts.shop :footer-categories="$footerCategories" :title="'Заказ оформлен — ' . config('app.name')">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="size-8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </span>

            <h1 class="mt-5 text-2xl font-bold text-slate-900">Спасибо за заказ!</h1>
            <p class="mt-2 text-sm text-slate-500">
                Номер заказа: <span class="font-semibold text-slate-800">{{ $order->number }}</span>.
                Мы свяжемся с вами по телефону <span class="font-semibold text-slate-800">{{ $order->customer_phone }}</span>
                для подтверждения и уточнения деталей доставки.
            </p>

            <div class="mt-6 grid gap-3 text-left sm:grid-cols-2">
                <div class="rounded-lg bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Получатель</div>
                    <div class="mt-1 text-sm font-medium text-slate-800">{{ $order->customer_name }}</div>
                    <div class="text-sm text-slate-500">{{ $order->customer_email }}</div>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Адрес доставки</div>
                    <div class="mt-1 text-sm font-medium text-slate-800">{{ $order->delivery_city }}</div>
                    <div class="text-sm text-slate-500">{{ $order->delivery_address }}</div>
                </div>
            </div>

            <div class="mt-6 rounded-lg border border-slate-200">
                <div class="border-b border-slate-200 px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                    Состав заказа
                </div>
                <ul class="divide-y divide-slate-100 text-left">
                    @foreach ($order->items as $item)
                        <li class="flex items-center justify-between gap-3 px-4 py-2 text-sm">
                            <span class="line-clamp-1 text-slate-700">{{ $item->product_name }} × {{ $item->quantity }}</span>
                            <span class="whitespace-nowrap font-semibold text-slate-900">
                                {{ number_format($item->line_total / 100, 0, ',', ' ') }} ₽
                            </span>
                        </li>
                    @endforeach
                </ul>
                <div class="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-sm">
                    <span class="font-semibold text-slate-500">Итого</span>
                    <span class="text-lg font-bold text-accent-600">
                        {{ number_format($order->total / 100, 0, ',', ' ') }} ₽
                    </span>
                </div>
            </div>

            <div class="mt-6 flex flex-col items-center gap-2 sm:flex-row sm:justify-center">
                <a
                    href="{{ route('storefront') }}"
                    class="rounded-md bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700"
                >
                    Продолжить покупки
                </a>
                <a
                    href="{{ route('login') }}"
                    class="rounded-md border border-slate-300 px-6 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:border-brand-400 hover:text-brand-700"
                >
                    Войти в личный кабинет
                </a>
            </div>
        </div>
    </div>
</x-layouts.shop>
