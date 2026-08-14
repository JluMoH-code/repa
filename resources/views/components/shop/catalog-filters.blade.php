@props(['filterGroups', 'priceMin', 'priceMax'])

@php
    $selected = request()->input('filter', []);
@endphp

<div class="space-y-4">
    {{-- Наличие --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
            <input
                type="checkbox"
                name="stock"
                value="1"
                @checked(request()->boolean('stock'))
                onchange="this.form.submit()"
                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
            >
            Только в наличии
        </label>
    </div>

    {{-- Цена --}}
    <div x-data="{ open: true }" class="rounded-xl border border-slate-200 bg-white p-4">
        <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-sm font-semibold text-slate-900">
            Цена, ₽
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                class="size-4 text-slate-400 transition-transform" :class="{ 'rotate-180': open }">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>

        <div x-show="open" x-transition class="mt-3 flex items-center gap-2">
            <input
                type="number"
                name="price_min"
                placeholder="{{ number_format($priceMin / 100, 0, ',', ' ') }}"
                value="{{ request('price_min') }}"
                class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500"
            >
            <span class="text-slate-400">—</span>
            <input
                type="number"
                name="price_max"
                placeholder="{{ number_format($priceMax / 100, 0, ',', ' ') }}"
                value="{{ request('price_max') }}"
                class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500"
            >
        </div>
        <button
            x-show="open"
            type="submit"
            class="mt-3 w-full rounded-md bg-slate-100 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-200"
        >
            Применить
        </button>
    </div>

    {{-- Динамические группы фильтров категории --}}
    @foreach ($filterGroups as $group)
        @if ($group->values->isNotEmpty())
            <div x-data="{ open: true }" class="rounded-xl border border-slate-200 bg-white p-4">
                <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-sm font-semibold text-slate-900">
                    {{ $group->name }}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        class="size-4 text-slate-400 transition-transform" :class="{ 'rotate-180': open }">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <div x-show="open" x-transition class="mt-3 space-y-2">
                    @foreach ($group->values as $value)
                        <label class="flex items-center justify-between gap-2 text-sm text-slate-600">
                            <span class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    name="filter[{{ $group->slug }}][]"
                                    value="{{ $value->slug }}"
                                    @checked(in_array($value->slug, (array) ($selected[$group->slug] ?? [])))
                                    onchange="this.form.submit()"
                                    class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                >
                                {{ $value->value }}
                            </span>
                            <span class="text-xs text-slate-400">{{ $value->products_count }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</div>
