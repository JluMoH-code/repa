@props(['items'])

<div x-data="{ active: 0, count: {{ count($items) }} }" class="rounded-xl border border-slate-200 bg-white p-4">
    <h3 class="mb-3 font-semibold text-slate-900">Похожие товары</h3>

    <div class="relative">
        <div class="overflow-hidden rounded-lg">
            @foreach ($items as $index => $item)
                <a
                    href="{{ $item['url'] ?? '#' }}"
                    x-show="active === {{ $index }}"
                    class="relative block aspect-[4/5] overflow-hidden rounded-lg bg-slate-50"
                    style="{{ $index === 0 ? '' : 'display: none;' }}"
                >
                    <img src="{{ $item['src'] }}" alt="{{ $item['name'] }}" class="size-full object-contain p-4">
                    <span class="absolute inset-x-0 bottom-0 bg-slate-900/75 px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide text-white">
                        {{ $item['name'] }}
                    </span>
                </a>
            @endforeach
        </div>

        <button
            @click="active = (active - 1 + count) % count"
            class="absolute top-1/2 left-1 flex size-8 -translate-y-1/2 items-center justify-center rounded-full bg-white text-slate-500 shadow ring-1 ring-slate-200 hover:text-brand-700"
            aria-label="Предыдущий товар"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
        </button>
        <button
            @click="active = (active + 1) % count"
            class="absolute top-1/2 right-1 flex size-8 -translate-y-1/2 items-center justify-center rounded-full bg-white text-slate-500 shadow ring-1 ring-slate-200 hover:text-brand-700"
            aria-label="Следующий товар"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </button>
    </div>

    <div class="mt-3 flex justify-center gap-2">
        @foreach ($items as $index => $item)
            <button
                @click="active = {{ $index }}"
                class="size-2 rounded-full"
                :class="active === {{ $index }} ? 'bg-accent-500' : 'bg-slate-200'"
                aria-label="Товар {{ $index + 1 }}"
            ></button>
        @endforeach
    </div>
</div>
