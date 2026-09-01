@props([
    'cities' => collect(),
    'name' => 'delivery_city',
    'id' => null,
    'value' => '',
    'placeholder' => 'Начните вводить — выберите из списка',
    'required' => false,
])

@php
    $cityList = Illuminate\Support\Js::from(
        $cities->map(fn ($city) => [
            'name' => $city->name,
            'region' => $city->region,
        ])->values()->all()
    );
    $initialValue = Illuminate\Support\Js::from($value);
@endphp

{{-- Кастомный автопоиск города в стиле витрины (вместо нативного <datalist>).
     Логика — в resources/js/city-autocomplete.js (глобальная Alpine-функция). --}}
<div
    x-data="cityAutocomplete({{ $cityList }}, {{ $initialValue }})"
    class="relative"
    data-city-autocomplete
>
    <input
        type="text"
        name="{{ $name }}"
        @if ($id) id="{{ $id }}" @endif
        x-model="query"
        @input="filter()"
        @focus="filter()"
        @click.outside="open = false"
        @keydown.down.prevent="move(1)"
        @keydown.up.prevent="move(-1)"
        @keydown.enter.prevent="if (!selectHighlighted()) $el.form?.requestSubmit()"
        autocomplete="off"
        placeholder="{{ $placeholder }}"
        @if ($required) required @endif
        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
    >

    {{-- Выпадающий список совпадений: показываем только когда есть что показать
         (иначе при фокусе на пустом поле вылезала бы пустая белая плашка). --}}
    <ul
        x-show="open && filtered.length > 0"
        x-transition.opacity.duration.150ms
        x-cloak
        class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
    >
        <template x-for="(city, index) in filtered" :key="city.name">
            <li>
                <button
                    type="button"
                    @click="select(city)"
                    @mouseenter="highlight = index"
                    class="block w-full px-3 py-2 text-left text-sm transition-colors"
                    :class="highlight === index ? 'bg-brand-50 text-brand-700' : 'text-slate-700'"
                    x-text="label(city)"
                ></button>
            </li>
        </template>
    </ul>

    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
