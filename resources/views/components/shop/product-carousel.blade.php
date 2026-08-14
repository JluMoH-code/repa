@props(['title', 'products'])

<section x-data="{
        scrollBy(amount) {
            this.$refs.track.scrollBy({ left: amount, behavior: 'smooth' });
        },
    }"
>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-slate-900">{{ $title }}</h2>

        <div class="flex gap-2">
            <button
                @click="scrollBy(-600)"
                class="flex size-9 items-center justify-center rounded-full bg-accent-500 text-white hover:bg-accent-600"
                aria-label="Прокрутить назад"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
            <button
                @click="scrollBy(600)"
                class="flex size-9 items-center justify-center rounded-full bg-accent-500 text-white hover:bg-accent-600"
                aria-label="Прокрутить вперёд"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>
        </div>
    </div>

    @if ($products->isEmpty())
        <p class="text-sm text-slate-400">Пока нет товаров для этого блока.</p>
    @else
        <div x-ref="track" class="scrollbar-hide flex gap-4 overflow-x-auto scroll-smooth pb-1">
            @foreach ($products as $product)
                <div class="w-44 shrink-0 sm:w-52">
                    <x-shop.product-card :product="$product" />
                </div>
            @endforeach
        </div>
    @endif
</section>
