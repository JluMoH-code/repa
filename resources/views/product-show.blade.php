<x-layouts.shop :footer-categories="$footerCategories" :title="$product->name . ' — ' . config('app.name')">
    <x-shop.breadcrumbs :items="$breadcrumbs" />

    <div class="mx-auto max-w-7xl px-4 pb-12">
        {{-- Верхняя часть с галереей, информацией и сайдбаром --}}
        <div class="grid gap-6 lg:grid-cols-[380px_1fr_320px]">
            {{-- Галерея --}}
            <div>
                <x-shop.product-gallery :images="$galleryImages" />
            </div>

            {{-- Информация о товаре --}}
            <div>
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-slate-900">{{ $product->name }}</h1>
                </div>

                <div class="mt-2 flex items-center gap-3 text-sm text-slate-500">
                    <span>Артикул: {{ $product->sku }}</span>
                    @if($product->manufacturer)
                        <span class="text-slate-300">•</span>
                        <span>Производитель: {{ $product->manufacturer->name }}</span>
                    @endif
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

                <div class="mt-6 flex flex-wrap items-center gap-4">
                    {{-- Цена в рублях (делим на 100, т.к. храним в копейках) --}}
                    <span class="text-3xl font-bold text-accent-600">
                        {{ number_format($product->price / 100, 0, ',', ' ') }} ₽
                    </span>
                    @if ($product->old_price)
                        <span class="text-lg text-slate-400 line-through">
                            {{ number_format($product->old_price / 100, 0, ',', ' ') }} ₽
                        </span>
                    @endif

                    @php
                        $available = $product->variants->isNotEmpty()
                            ? $product->variants->contains('in_stock', true)
                            : $product->in_stock;
                        $cartQuantity = app(\App\Actions\Cart\CartManager::class)->quantity($product->id);
                        $isFavorite = app(\App\Actions\Favorites\FavoriteManager::class)->has($product->id);
                    @endphp

                    <div
                        x-data="{
                            qty: 1,
                            inCart: {{ $cartQuantity }},
                            buy() {
                                addToCart({{ $product->id }}, this.qty, (payload) => { this.inCart = payload.quantity; });
                            },
                            increase() {
                                addToCart({{ $product->id }}, 1, (payload) => { this.inCart = payload.quantity; });
                            },
                            decrease() {
                                if (this.inCart <= 1) {
                                    removeFromCart({{ $product->id }}, (payload) => { this.inCart = payload.quantity ?? 0; });
                                } else {
                                    updateCartQuantity({{ $product->id }}, this.inCart - 1, (payload) => { this.inCart = payload.quantity; });
                                }
                            },
                        }"
                        @cart-synced.window="inCart = $event.detail.quantities[{{ $product->id }}] ?? 0"
                        class="flex flex-wrap items-center gap-3"
                    >
                        @if ($available)
                            {{-- Товара нет в корзине: выбор количества + кнопка «В корзину» --}}
                            <template x-if="! inCart">
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="inline-flex items-center rounded-md border border-slate-300 bg-white">
                                        <button
                                            type="button"
                                            @click="qty = Math.max(1, qty - 1)"
                                            class="flex size-10 items-center justify-center text-slate-500 hover:bg-slate-100"
                                            aria-label="Уменьшить количество"
                                        >−</button>
                                        <input
                                            type="number"
                                            x-model.number="qty"
                                            min="1"
                                            max="99"
                                            class="w-14 border-x border-slate-300 py-2 text-center text-sm focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                        >
                                        <button
                                            type="button"
                                            @click="qty = Math.min(99, qty + 1)"
                                            class="flex size-10 items-center justify-center text-slate-500 hover:bg-slate-100"
                                            aria-label="Увеличить количество"
                                        >+</button>
                                    </div>

                                    <button
                                        type="button"
                                        @click="buy()"
                                        class="rounded-md bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700"
                                    >
                                        В корзину
                                    </button>
                                </div>
                            </template>

                            {{-- Товар уже в корзине: компактный блок «В корзине» (количество + переход) --}}
                            <template x-if="inCart">
                                <div class="inline-flex items-center gap-3 whitespace-nowrap">
                                    <div class="inline-flex items-center rounded-md border border-accent-300 bg-accent-50">
                                        <button
                                            type="button"
                                            @click="decrease()"
                                            class="flex size-9 items-center justify-center text-accent-700 transition-colors hover:bg-accent-100"
                                            :aria-label="inCart > 1 ? 'Уменьшить количество в корзине' : 'Удалить товар из корзины'"
                                        >
                                            <span x-show="inCart > 1" aria-hidden="true">−</span>
                                            <svg
                                                x-show="inCart <= 1"
                                                aria-hidden="true"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.6"
                                                class="size-5"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                        <span
                                            class="w-10 border-x border-accent-200 py-2 text-center text-sm font-semibold text-accent-700"
                                            x-text="inCart"
                                        ></span>
                                        <button
                                            type="button"
                                            @click="increase()"
                                            class="flex size-9 items-center justify-center text-accent-700 transition-colors hover:bg-accent-100"
                                            aria-label="Увеличить количество в корзине"
                                        >+</button>
                                    </div>
                                    <a
                                        href="{{ route('cart.index') }}"
                                        class="inline-flex items-center gap-1.5 rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-700"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                        В корзине
                                    </a>
                                </div>
                            </template>
                        @else
                            <button
                                type="button"
                                disabled
                                class="cursor-not-allowed rounded-md bg-slate-300 px-6 py-3 text-sm font-semibold text-white"
                            >
                                Нет в наличии
                            </button>
                        @endif
                    </div>

                    <button
                        type="button"
                        data-favorite-id="{{ $product->id }}"
                        data-active="{{ $isFavorite ? '1' : '0' }}"
                        class="flex size-11 items-center justify-center rounded-md border transition-colors {{ $isFavorite ? 'border-red-200 bg-red-50 text-red-500' : 'border-slate-200 text-slate-400 hover:border-red-200 hover:text-red-500' }}"
                        aria-label="В избранное"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="{{ $isFavorite ? 'currentColor' : 'none' }}"
                            stroke="currentColor"
                            stroke-width="1.6"
                            class="favorite-heart-icon size-5"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                        </svg>
                    </button>

                    <button type="button" class="flex size-11 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:text-brand-700 transition-colors" aria-label="Сравнить">
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
                        <span class="text-sm font-medium text-slate-700">Оплата заказа при получении</span>
                    </div>
                </div>

                <x-shop.similar-products :items="$similarProducts" />
            </div>
        </div>

        {{-- Табы с описанием, характеристиками и отзывами (ширина = галерея + инфо) --}}
        <div class="mt-8">
            <div class="lg:col-span-2 lg:col-start-1 lg:col-end-3">
                <x-shop.product-tabs :tabs="$tabs" />
            </div>
        </div>
    </div>
</x-layouts.shop>