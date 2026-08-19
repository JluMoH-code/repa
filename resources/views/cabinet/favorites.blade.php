<x-layouts.shop :footer-categories="$footerCategories" :title="'Избранное — ' . config('app.name')">
    <div class="mx-auto max-w-7xl px-4 py-6">
        <h1 class="text-2xl font-bold text-slate-900">Личный кабинет</h1>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[260px_1fr]">
            <x-cabinet.sidebar active="favorites" />

            <div id="favorites-page">
                @if ($lines->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white py-16 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" class="mx-auto size-16 text-slate-300">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                        </svg>
                        <p class="mt-4 text-lg font-semibold text-slate-700">В избранном пока пусто</p>
                        <p class="mt-1 text-sm text-slate-500">Отмечайте товары сердечком на карточках — они появятся здесь.</p>
                        <a href="{{ route('storefront') }}" class="mt-6 inline-block rounded-md bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                            Перейти в каталог
                        </a>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($lines as $product)
                            @php
                                $image = $product->images->firstWhere('is_main', true) ?? $product->images->first();
                            @endphp
                            <div
                                class="flex flex-wrap items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                                data-favorite-row="{{ $product->id }}"
                            >
                                <a href="{{ route('products.show', $product) }}" class="size-20 shrink-0 overflow-hidden rounded-lg bg-slate-50">
                                    @if ($image)
                                        <img src="{{ Storage::disk('public')->url($image->path) }}" alt="{{ $product->name }}" class="size-full object-cover">
                                    @else
                                        <span class="flex size-full items-center justify-center text-xs text-slate-400">Нет фото</span>
                                    @endif
                                </a>

                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('products.show', $product) }}" class="line-clamp-2 font-medium text-slate-800 hover:text-brand-700">
                                        {{ $product->name }}
                                    </a>
                                    <span class="mt-1 block text-lg font-bold text-accent-600">
                                        {{ number_format($product->price / 100, 0, ',', ' ') }} ₽
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="addToCart({{ $product->id }})"
                                        class="rounded-md bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700"
                                    >
                                        В корзину
                                    </button>
                                    <button
                                        type="button"
                                        data-favorite-remove="{{ $product->id }}"
                                        class="flex size-11 items-center justify-center rounded-md border border-slate-200 text-red-500 transition-colors hover:bg-red-50"
                                        aria-label="Удалить из избранного"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.6" class="size-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.shop>
