<x-layouts.shop :footer-categories="$footerCategories" :title="'Редактирование заказа ' . $order->number . ' — ' . config('app.name')">
    <div class="mx-auto max-w-7xl px-4 py-6">
        <h1 class="text-2xl font-bold text-slate-900">Личный кабинет</h1>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[260px_1fr]">
            <x-cabinet.sidebar active="orders" />

            <div class="space-y-6">
                <div>
                    <a href="{{ route('cabinet.orders.show', $order) }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                        Назад к заказу {{ $order->number }}
                    </a>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-lg font-semibold text-slate-900">Редактирование заказа</h2>
                    <span class="inline-block rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                        {{ $order->status->getLabel() }}
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

                <form method="POST" action="{{ route('cabinet.orders.update', $order) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-900">Контактные данные</h3>
                        <p class="mt-1 text-sm text-slate-500">Изменения возможны до отправки заказа.</p>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="customer_name" class="mb-1 block text-sm font-medium text-slate-700">Имя</label>
                                <input
                                    id="customer_name"
                                    type="text"
                                    name="customer_name"
                                    value="{{ old('customer_name', $order->customer_name) }}"
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
                                    value="{{ old('customer_email', $order->customer_email) }}"
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
                                    value="{{ old('customer_phone', $order->customer_phone) }}"
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
                        <h3 class="text-base font-semibold text-slate-900">Адрес доставки</h3>

                        <div class="mt-5 grid gap-4 sm:grid-cols-3">
                            <div class="sm:col-span-2">
                                <label for="delivery_city" class="mb-1 block text-sm font-medium text-slate-700">Город</label>
                                <input
                                    id="delivery_city"
                                    type="text"
                                    name="delivery_city"
                                    value="{{ old('delivery_city', $order->delivery_city) }}"
                                    required
                                    maxlength="120"
                                    list="cities-datalist"
                                    autocomplete="off"
                                    placeholder="Начните вводить — выберите из списка"
                                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                                >
                                <datalist id="cities-datalist">
                                    @foreach ($cities as $city)
                                        <option value="{{ $city->name }}">{{ $city->region ? $city->name . ' (' . $city->region . ')' : $city->name }}</option>
                                    @endforeach
                                </datalist>
                                @error('delivery_city')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="delivery_postcode" class="mb-1 block text-sm font-medium text-slate-700">Индекс</label>
                                <input
                                    id="delivery_postcode"
                                    type="text"
                                    name="delivery_postcode"
                                    value="{{ old('delivery_postcode', $order->delivery_postcode) }}"
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
                                    value="{{ old('delivery_address', $order->delivery_address) }}"
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
                        <h3 class="text-base font-semibold text-slate-900">Комментарий к заказу</h3>
                        <textarea
                            id="comment"
                            name="comment"
                            rows="3"
                            maxlength="1000"
                            placeholder="Пожелания по доставке, удобное время для звонка и т. п."
                            class="mt-3 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                        >{{ old('comment', $order->comment) }}</textarea>
                        @error('comment')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </section>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="submit"
                            class="rounded-md bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700"
                        >
                            Сохранить изменения
                        </button>
                        <a
                            href="{{ route('cabinet.orders.show', $order) }}"
                            class="rounded-md border border-slate-300 bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:border-brand-400 hover:text-brand-700"
                        >
                            Отмена
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.shop>
