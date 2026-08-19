@props(['slides'])

@php
    // Фоны слайдов задаём здесь, в blade (Tailwind v4 с source(none) сканирует
    // только blade/js): классы, заданные в контроллере, в CSS не попадали —
    // у hero-блока не было фона вообще. Сплошные цвета бренда, как у карточек.
    $slideBackgrounds = [
        'brand' => 'bg-brand-600',
        'accent' => 'bg-accent-600',
    ];
@endphp

<div x-data="{ active: 0, count: {{ count($slides) }} }" class="relative overflow-hidden rounded-xl">
    <div class="relative h-64 md:h-80">
        @foreach ($slides as $index => $slide)
            <div
                x-show="active === {{ $index }}"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                class="absolute inset-0 flex items-center justify-between px-8 md:px-12 {{ $slideBackgrounds[$slide['theme'] ?? 'brand'] }}"
                style="{{ $index === 0 ? '' : 'display: none;' }}"
            >
                <div class="max-w-md text-white">
                    <p class="text-sm font-medium uppercase tracking-wide text-white/80">{{ $slide['eyebrow'] }}</p>
                    <h2 class="mt-1 text-2xl font-bold md:text-3xl">{{ $slide['title'] }}</h2>
                    <p class="mt-2 text-sm text-white/90 md:text-base">{{ $slide['subtitle'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <button
        @click="active = (active - 1 + count) % count"
        class="absolute top-1/2 left-3 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-accent-600 shadow hover:bg-white"
        aria-label="Предыдущий слайд"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
    </button>
    <button
        @click="active = (active + 1) % count"
        class="absolute top-1/2 right-3 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-accent-600 shadow hover:bg-white"
        aria-label="Следующий слайд"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
        </svg>
    </button>

    <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-2">
        @foreach ($slides as $index => $slide)
            <button
                @click="active = {{ $index }}"
                class="size-2.5 rounded-full transition-colors"
                :class="active === {{ $index }} ? 'bg-accent-500' : 'bg-white/60'"
                aria-label="Слайд {{ $index + 1 }}"
            ></button>
        @endforeach
    </div>
</div>
