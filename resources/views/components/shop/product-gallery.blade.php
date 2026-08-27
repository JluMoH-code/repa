@props(['images'])

<div
    x-data="{
        active: 0,
        scrollLeft: 0,
        scrollWidth: 0,
        clientWidth: 0,
        init() {
            this.updateScrollMetrics();
        },
        updateScrollMetrics() {
            const el = this.$refs.thumbs;
            if (! el) {
                return;
            }
            this.scrollLeft = el.scrollLeft;
            this.scrollWidth = el.scrollWidth;
            this.clientWidth = el.clientWidth;
        },
        scrollBy(direction) {
            this.$refs.thumbs.scrollBy({ left: direction * 240, behavior: 'smooth' });
        },
        canScrollLeft() {
            return this.scrollLeft > 4;
        },
        canScrollRight() {
            return this.scrollLeft + this.clientWidth < this.scrollWidth - 4;
        },
    }"
    @resize.window="updateScrollMetrics()"
    class="relative"
>
    {{-- Главное изображение --}}
    <div class="flex aspect-square items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white">
        @foreach ($images as $index => $image)
            <img
                x-show="active === {{ $index }}"
                src="{{ $image['src'] }}"
                alt="{{ $image['label'] }}"
                class="size-full object-contain p-6"
                style="{{ $index === 0 ? '' : 'display: none;' }}"
            >
        @endforeach
    </div>

    {{-- Миниатюры: одна строка, листается; стрелки поверх крайних картинок --}}
    @if (count($images) > 1)
        <div class="relative mt-3">
            {{-- Стрелка влево: показывается, только пока можно листать влево --}}
            <button
                type="button"
                x-show="canScrollLeft()"
                x-cloak
                @click="scrollBy(-1)"
                class="absolute top-1/2 left-0 z-10 flex size-8 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white/95 text-slate-600 shadow-sm transition-colors hover:bg-white hover:text-slate-900"
                aria-label="Предыдущее фото"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>

            {{-- Стрелка вправо: показывается, только пока можно листать вправо --}}
            <button
                type="button"
                x-show="canScrollRight()"
                x-cloak
                @click="scrollBy(1)"
                class="absolute top-1/2 right-0 z-10 flex size-8 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white/95 text-slate-600 shadow-sm transition-colors hover:bg-white hover:text-slate-900"
                aria-label="Следующее фото"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>

            <div
                x-ref="thumbs"
                @scroll.passive="updateScrollMetrics()"
                class="scrollbar-hide flex gap-3 overflow-x-auto scroll-smooth"
            >
                @foreach ($images as $index => $image)
                    <button
                        type="button"
                        @click="active = {{ $index }}"
                        class="relative shrink-0 overflow-hidden rounded-lg border transition-all duration-200"
                        :class="active === {{ $index }}
                            ? 'border-brand-500 opacity-100 ring-1 ring-brand-500'
                            : 'border-slate-200 opacity-40 saturate-50 hover:opacity-75'"
                    >
                        <img src="{{ $image['src'] }}" alt="{{ $image['label'] }}" class="aspect-square w-20 object-contain p-1.5 sm:w-24">
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
