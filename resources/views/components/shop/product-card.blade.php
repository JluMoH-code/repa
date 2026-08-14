@props(['product'])

@php
    $image = $product->images->firstWhere('is_main', true) ?? $product->images->first();
@endphp

<div class="flex h-full flex-col rounded-xl border border-slate-200 bg-white p-4 transition-shadow hover:shadow-md">
    <a href="{{ route('products.show', $product) }}" class="mb-3 flex aspect-square items-center justify-center overflow-hidden rounded-lg bg-slate-50">
        @if ($image)
            <img src="{{ Storage::disk('public')->url($image->path) }}" alt="{{ $product->name }}" class="size-full object-cover">
        @else
            <span class="text-xs text-slate-400">Нет фото</span>
        @endif
    </a>

    <a href="{{ route('products.show', $product) }}" class="line-clamp-2 text-sm font-medium text-slate-800 hover:text-brand-700">
        {{ $product->name }}
    </a>

    <div class="mt-auto pt-3">
        <div class="flex items-baseline gap-2">
            <span class="text-lg font-bold text-slate-900">
                {{ number_format($product->price / 100, 0, ',', ' ') }} ₽
            </span>
            @if ($product->old_price)
                <span class="text-sm text-slate-400 line-through">
                    {{ number_format($product->old_price / 100, 0, ',', ' ') }} ₽
                </span>
            @endif
        </div>

        <button type="button" class="mt-2 w-full rounded-md bg-slate-100 py-2 text-sm font-medium text-slate-700 hover:bg-accent-500 hover:text-white">
            Купить
        </button>
    </div>
</div>
