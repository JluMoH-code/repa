@props(['images'])

<div x-data="{ active: 0 }">
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

    <div class="mt-3 grid grid-cols-4 gap-3">
        @foreach ($images as $index => $image)
            <button
                @click="active = {{ $index }}"
                type="button"
                class="group relative overflow-hidden rounded-lg border bg-white"
                :class="active === {{ $index }} ? 'border-brand-500 ring-1 ring-brand-500' : 'border-slate-200'"
            >
                <img src="{{ $image['src'] }}" alt="{{ $image['label'] }}" class="aspect-square w-full object-contain p-2">
                <span class="absolute inset-x-0 bottom-0 bg-slate-900/70 px-1.5 py-1 text-[10px] font-medium text-white">
                    {{ $image['label'] }}
                </span>
            </button>
        @endforeach
    </div>
</div>
