@props(['min' => 1, 'value' => 1])

<div x-data="{ qty: {{ $value }} }" class="inline-flex items-center rounded-md border border-slate-300">
    <button
        type="button"
        @click="qty = Math.max({{ $min }}, qty - 1)"
        class="flex size-8 items-center justify-center text-slate-500 hover:bg-slate-100"
        aria-label="Уменьшить количество"
    >−</button>
    <input
        type="number"
        x-model.number="qty"
        min="{{ $min }}"
        class="w-12 border-x border-slate-300 py-1 text-center text-sm focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
    >
    <button
        type="button"
        @click="qty = qty + 1"
        class="flex size-8 items-center justify-center text-slate-500 hover:bg-slate-100"
        aria-label="Увеличить количество"
    >+</button>
</div>
