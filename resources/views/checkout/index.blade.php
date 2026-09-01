<x-layouts.shop :footer-categories="$footerCategories" :title="'Оформление заказа — ' . config('app.name')">
    <div class="mx-auto max-w-7xl px-4 py-6">
        <h1 class="text-2xl font-bold text-slate-900">Оформление заказа</h1>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.store') }}" class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_360px]">
            @csrf

            {{-- Левая колонка: контакты + адрес + комментарий --}}
            <div class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Контактные данные</h2>
                    <p class="mt-1 text-sm text-slate-500">Мы свяжемся с вами для подтверждения заказа.</p>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="customer_name" class="mb-1 block text-sm font-medium text-slate-700">Имя</label>
                            <input
                                id="customer_name"
                                type="text"
                                name="customer_name"
                                value="{{ $defaults['customer_name'] ?? '' }}"
                                required
                                maxlength="120"
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                            >
                            @error('customer_name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="customer_email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                            <input
                                id="customer_email"
                                type="email"
                                name="customer_email"
                                value="{{ $defaults['customer_email'] ?? '' }}"
                                required
                                maxlength="180"
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                            >
                            @error('customer_email')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="customer_phone" class="mb-1 block text-sm font-medium text-slate-700">Телефон</label>
                            <input
                                id="customer_phone"
                                type="tel"
                                name="customer_phone"
                                value="{{ $defaults['customer_phone'] ?? '' }}"
                                required
                                placeholder="+7 (999) 123-45-67"
                                maxlength="30"
                                data-phone-mask
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                            >
                            @error('customer_phone')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Адрес доставки</h2>

                    <div class="mt-5 grid gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label for="delivery_city" class="mb-1 block text-sm font-medium text-slate-700">Город</label>
                            <x-shop.city-autocomplete
                                :cities="$cities"
                                name="delivery_city"
                                id="delivery_city"
                                :value="old('delivery_city')"
                                required
                            />
                        </div>

                        <div>
                            <label for="delivery_postcode" class="mb-1 block text-sm font-medium text-slate-700">Индекс</label>
                            <input
                                id="delivery_postcode"
                                type="text"
                                name="delivery_postcode"
                                value="{{ old('delivery_postcode') }}"
                                maxlength="10"
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                            >
                            @error('delivery_postcode')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-3">
                            <label for="delivery_address" class="mb-1 block text-sm font-medium text-slate-700">Улица, дом, квартира</label>
                            <input
                                id="delivery_address"
                                type="text"
                                name="delivery_address"
                                value="{{ old('delivery_address') }}"
                                required
                                maxlength="255"
                                placeholder="ул. Цветочная, д. 12, кв. 34"
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                            >
                            @error('delivery_address')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Комментарий к заказу</h2>
                    <textarea
                        id="comment"
                        name="comment"
                        rows="3"
                        maxlength="1000"
                        placeholder="Пожелания по доставке, удобное время для звонка и т. п."
                        class="mt-3 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                    >{{ old('comment') }}</textarea>
                    @error('comment')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </section>
            </div>

            {{-- Правая колонка: итог заказа --}}
            <aside class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-4">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Ваш заказ</h2>

                <dl class="mt-4 space-y-2 text-sm">
                    @foreach ($lines as $line)
                        <div class="flex justify-between gap-3">
                            <dt class="line-clamp-1 text-slate-500">
                                {{ $line['product']->name }} × {{ $line['quantity'] }}
                            </dt>
                            <dd class="whitespace-nowrap font-medium text-slate-800">
                                {{ number_format($line['line_total'] / 100, 0, ',', ' ') }} ₽
                            </dd>
                        </div>
                    @endforeach
                </dl>

                <div class="mt-4 border-t border-slate-100 pt-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-500">Итого:</span>
                        <span class="text-2xl font-bold text-accent-600">
                            {{ number_format($total / 100, 0, ',', ' ') }} ₽
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">
                        Стоимость доставки рассчитывается при подтверждении заказа.
                    </p>
                </div>

                <button
                    type="submit"
                    class="mt-5 w-full rounded-md bg-brand-600 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700"
                >
                    Подтвердить заказ
                </button>

                <a href="{{ route('cart.index') }}" class="mt-3 block text-center text-sm font-medium text-slate-600 hover:text-brand-700">
                    Вернуться в корзину
                </a>

                @error('cart')
                    <p class="mt-3 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </aside>
        </form>
    </div>
</x-layouts.shop>
