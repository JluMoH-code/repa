@php
    $image = $product->images->firstWhere('is_main', true) ?? $product->images->first();
    $price = number_format($product->price / 100, 0, ',', ' ');
    $oldPrice = $product->old_price ? number_format($product->old_price / 100, 0, ',', ' ') : null;
@endphp
<a
    href="{{ route('products.show', $product) }}"
    target="_blank"
    rel="noopener"
    title="Открыть страницу товара"
    style="max-width: 260px; display: inline-block; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; padding: 16px; font-family: Inter, Arial, sans-serif; text-decoration: none; transition: box-shadow .15s ease;"
    onmouseover="this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,.1)'"
    onmouseout="this.style.boxShadow='none'"
>
    <div style="aspect-ratio: 1 / 1; border-radius: 8px; background: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden;">
        @if ($image)
            <img src="{{ Storage::disk('public')->url($image->path) }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover;">
        @else
            <span style="font-size: 12px; color: #94a3b8;">Нет фото</span>
        @endif
    </div>

    <span style="display: inline-block; margin-top: 12px; border-radius: 9999px; padding: 2px 10px; font-size: 11px; font-weight: 500;
        {{ $product->in_stock ? 'background: #d1fae5; color: #065f46;' : 'background: #e2e8f0; color: #64748b;' }}">
        {{ $product->in_stock ? 'В наличии' : 'Нет в наличии' }}
    </span>

    <p style="margin: 10px 0 0; font-size: 14px; font-weight: 500; color: #1e293b; line-height: 1.35;">{{ $product->name }}</p>

    <div style="margin-top: 10px; display: flex; align-items: baseline; gap: 8px;">
        <span style="font-size: 18px; font-weight: 700; color: #ea580c;">{{ $price }} ₽</span>
        @if ($oldPrice)
            <span style="font-size: 14px; color: #94a3b8; text-decoration: line-through;">{{ $oldPrice }} ₽</span>
        @endif
    </div>

    <div style="margin-top: 12px; border-radius: 6px; background: #16a34a; color: #fff; text-align: center; padding: 8px 0; font-size: 14px; font-weight: 500;">
        Открыть товар
    </div>
</a>
