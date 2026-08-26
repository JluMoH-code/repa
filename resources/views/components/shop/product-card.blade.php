@props(['product'])

@php
    $image = $product->images->firstWhere('is_main', true) ?? $product->images->first();
    $variants = $product->relationLoaded('variants') ? $product->variants : collect();
    $hasVariants = $variants->isNotEmpty();
    $firstVariant = $variants->first();
    $basePrice = $hasVariants ? $firstVariant->price : $product->price;
    $available = $hasVariants ? $variants->contains('in_stock', true) : $product->in_stock;
    $isFavorite = app(\App\Actions\Favorites\FavoriteManager::class)->has($product->id);
    $cartQuantity = app(\App\Actions\Cart\CartManager::class)->quantity($product->id);
@endphp

<div
    x-data="{
        price: {{ $basePrice }},
        available: {{ $available ? 'true' : 'false' }},
        inCart: {{ $cartQuantity }},
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
        @if ($hasVariants)
        setVariant(id) {
            const variants = {{ Illuminate\Support\Js::from($variants->map(fn ($v) => ['id' => $v->id, 'price' => $v->price, 'in_stock' => $v->in_stock])) }};
            const v = variants.find(v => v.id == id);
            if (v) { this.price = v.price; this.available = v.in_stock; }
        },
        @endif
    }"
    @cart-synced.window="inCart = $event.detail.quantities[{{ $product->id }}] ?? 0"
    class="flex h-full flex-col rounded-xl border border-slate-200 bg-white p-4 transition-shadow hover:shadow-md"
>
    <a href="{{ route('products.show', $product) }}" class="relative mb-3 flex aspect-square items-center justify-center overflow-hidden rounded-lg bg-slate-50">
        @if ($image)
            <img src="{{ Storage::disk('public')->url($image->path) }}" alt="{{ $product->name }}" class="size-full object-cover">
        @else
            <span class="text-xs text-slate-400">Нет фото</span>
        @endif

        <span
            class="absolute top-2 left-2 rounded-full px-2 py-0.5 text-[11px] font-medium"
            :class="available ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-500'"
            x-text="available ? 'В наличии' : 'Нет в наличии'"
        ></span>
    </a>

    <a href="{{ route('products.show', $product) }}" class="line-clamp-2 text-sm font-medium text-slate-800 hover:text-brand-700">
        {{ $product->name }}
    </a>

    @if ($product->rating)
        <div class="mt-1 flex items-center gap-0.5">
            @for ($i = 1; $i <= 5; $i++)
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="size-3.5 {{ $i <= round($product->rating) ? 'fill-accent-500' : 'fill-slate-200' }}">
                    <path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1 1 5.8L10 14.9l-5.21 2.62 1-5.8-4.21-4.1 5.82-.85L10 1.5z" />
                </svg>
            @endfor
            <span class="ml-1 text-xs text-slate-400">{{ number_format($product->rating, 1) }}</span>
        </div>
    @endif

    <div class="mt-auto pt-3">
        @if ($hasVariants)
            <select
                class="mb-2 w-full rounded-md border border-slate-300 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-brand-500"
                @change="setVariant($event.target.value)"
            >
                @foreach ($variants as $variant)
                    <option value="{{ $variant->id }}" @disabled(! $variant->in_stock)>
                        {{ $variant->label }}{{ $variant->in_stock ? '' : ' (нет в наличии)' }}
                    </option>
                @endforeach
            </select>
        @endif

        <div class="flex items-baseline gap-2">
            <span class="text-lg font-bold text-slate-900" x-text="new Intl.NumberFormat('ru-RU').format(Math.round(price / 100)) + ' ₽'"></span>
            @if (! $hasVariants && $product->old_price)
                <span class="text-sm text-slate-400 line-through">
                    {{ number_format($product->old_price / 100, 0, ',', ' ') }} ₽
                </span>
            @endif
        </div>

        <div class="mt-2 flex gap-2">
            {{-- Товара нет в корзине: кнопка «Купить» --}}
            <template x-if="! inCart">
                <button
                    type="button"
                    @click="increase()"
                    class="flex-1 rounded-md py-2 text-sm font-medium transition-colors"
                    :class="available ? 'bg-slate-100 text-slate-700 hover:bg-accent-500 hover:text-white' : 'bg-slate-100 text-slate-400 cursor-not-allowed'"
                    :disabled="! available"
                >
                    <span x-text="available ? 'Купить' : 'Нет в наличии'"></span>
                </button>
            </template>

            {{-- Товар в корзине: количество в одну строку (иконка корзины + счётчик) --}}
            <template x-if="inCart">
                <div class="flex flex-1 items-center justify-between gap-0.5 rounded-md border border-accent-300 bg-accent-50 px-1 py-1">
                    <button
                        type="button"
                        @click="decrease()"
                        class="flex size-7 shrink-0 items-center justify-center rounded text-accent-700 transition-colors hover:bg-accent-100"
                        aria-label="Уменьшить количество в корзине"
                    >−</button>
                    <a
                        href="{{ route('cart.index') }}"
                        class="flex min-w-0 items-center justify-center gap-1 px-0.5"
                        title="Перейти в корзину"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="size-3.5 shrink-0 text-accent-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.708 2.602-7.201.245-1.007-.44-2.049-1.4-2.049H5.106M7.5 14.25 5.106 5.25M6 18.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        <span class="text-sm font-bold text-accent-700" x-text="inCart"></span>
                    </a>
                    <button
                        type="button"
                        @click="increase()"
                        class="flex size-7 shrink-0 items-center justify-center rounded text-accent-700 transition-colors hover:bg-accent-100"
                        aria-label="Увеличить количество в корзине"
                    >+</button>
                </div>
            </template>

            <button
                type="button"
                data-favorite-id="{{ $product->id }}"
                data-active="{{ $isFavorite ? '1' : '0' }}"
                class="flex w-11 shrink-0 items-center justify-center rounded-md border transition-colors {{ $isFavorite ? 'border-red-200 bg-red-50 text-red-500' : 'border-slate-200 text-slate-400 hover:border-red-200 hover:text-red-500' }}"
                aria-label="Добавить в избранное"
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
        </div>
    </div>
</div>
