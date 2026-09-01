<x-layouts.shop :footer-categories="$footerCategories" :title="'Оформление заказа — ' . config('app.name')">
    <div class="mx-auto max-w-7xl px-4 py-6">
        <h1 class="text-2xl font-bold text-slate-900">Оформление заказа</h1>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('checkout.store') }}"
            class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_360px]"
            x-data="{ method: {{ Illuminate\Support\Js::from(old('delivery_method', 'pickup')) }} }"
        >
            @csrf

            {{-- Левая колонка: контакты + способ получения + комментарий --}}
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

                {{-- Способ получения: самовывоз / доставка по адресу (до адреса) --}}
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Способ получения</h2>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition-colors"
                            :class="method === 'pickup' ? 'border-brand-500 bg-brand-50' : 'border-slate-200 hover:border-brand-300'"
                        >
                            <input type="radio" name="delivery_method" value="pickup" x-model="method" class="mt-0.5 accent-brand-600">
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Самовывоз</span>
                                <span class="mt-0.5 block text-xs text-slate-500">
                                    Заберёте заказ из магазина — {{ $shopAddress }}
                                </span>
                            </span>
                        </label>

                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition-colors"
                            :class="method === 'delivery' ? 'border-brand-500 bg-brand-50' : 'border-slate-200 hover:border-brand-300'"
                        >
                            <input type="radio" name="delivery_method" value="delivery" x-model="method" class="mt-0.5 accent-brand-600">
                            <span>
                                <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                    Доставка по адресу
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">В разработке</span>
                                </span>
                                <span class="mt-0.5 block text-xs text-slate-500">Почта России, СДЭК, Яндекс Доставка и другие</span>
                            </span>
                        </label>
                    </div>

                    @error('delivery_method')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    {{-- Самовывоз: адрес и часы магазина --}}
                    <div x-show="method === 'pickup'" x-cloak class="mt-4 rounded-lg bg-slate-50 p-4 text-sm">
                        <div class="flex items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="mt-0.5 size-4 shrink-0 text-brand-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                            <div>
                                <div class="font-semibold text-slate-800">Магазин Repa</div>
                                <div class="text-slate-600">{{ $shopAddress }}</div>
                                @if ($shopHours)
                                    <div class="text-slate-500">Часы работы: {{ $shopHours }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Доставка по адресу: службы доставки (в разработке) --}}
                    <div x-show="method === 'delivery'" x-cloak class="mt-4 space-y-3">
                        <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700">
                            Доставка по адресу находится в разработке — пока вы можете оформить только самовывоз.
                        </p>

                        <div>
                            <span class="mb-2 block text-sm font-medium text-slate-700">Служба доставки</span>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($deliveryServices as $service)
                                    <label class="flex cursor-not-allowed items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 opacity-60">
                                        <input type="radio" name="delivery_service" value="{{ $service }}" disabled class="accent-brand-600">
                                        <span class="flex flex-1 items-center justify-between gap-2 text-sm text-slate-600">
                                            {{ $service }}
                                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[11px] font-semibold text-slate-500">В разработке</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Адрес доставки — заготовка для будущей доставки (скрыт при самовывозе) --}}
                <section
                    x-show="method === 'delivery'"
                    x-cloak
                    class="rounded-xl border border-slate-200 bg-white p-5 opacity-70 shadow-sm"
                >
                    <h2 class="text-base font-semibold text-slate-900">Адрес доставки</h2>

                    <div class="mt-5 grid gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label for="delivery_city" class="mb-1 block text-sm font-medium text-slate-700">Город</label>
                            <x-shop.city-autocomplete
                                :cities="$cities"
                                name="delivery_city"
                                id="delivery_city"
                                :value="old('delivery_city')"
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
                    :disabled="method === 'delivery'"
                    class="mt-5 w-full rounded-md bg-brand-600 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                >
                    Подтвердить заказ
                </button>

                {{-- Подсказка при выбранной доставке --}}
                <p x-show="method === 'delivery'" x-cloak class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-center text-xs font-medium text-amber-700">
                    Доставка в разработке — выберите самовывоз, чтобы оформить заказ.
                </p>

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
