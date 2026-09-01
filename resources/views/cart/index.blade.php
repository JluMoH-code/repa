<x-layouts.shop :footer-categories="$footerCategories" :title="'Корзина — ' . config('app.name')">
    <div class="mx-auto max-w-7xl px-4 py-6" id="cart-page">
        <h1 class="text-2xl font-bold text-slate-900">Корзина</h1>

        @if ($lines->isEmpty())
            {{-- Пустое состояние --}}
            <div class="mt-8 rounded-xl border border-dashed border-slate-300 bg-white py-20 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" class="mx-auto size-16 text-slate-300">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.708 2.602-7.201.245-1.007-.44-2.049-1.4-2.049H5.106M7.5 14.25 5.106 5.25M6 18.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                </svg>
                <p class="mt-4 text-lg font-semibold text-slate-700">Корзина пуста</p>
                <p class="mt-1 text-sm text-slate-500">Добавьте понравившиеся семена — и они появятся здесь.</p>
                <a href="{{ route('storefront') }}" class="mt-6 inline-block rounded-md bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                    Перейти в каталог
                </a>
            </div>
        @else
            <div class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_320px]">
                {{-- Список товаров --}}
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                                    <th class="px-4 py-3 font-medium">Товар</th>
                                    <th class="px-4 py-3 font-medium">Цена</th>
                                    <th class="px-4 py-3 font-medium">Количество</th>
                                    <th class="px-4 py-3 text-right font-medium">Сумма</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lines as $line)
                                    @php
                                        $product = $line['product'];
                                        $image = $product->images->firstWhere('is_main', true) ?? $product->images->first();
                                    @endphp
                                    <tr data-cart-line="{{ $product->id }}" class="border-b border-slate-100 last:border-0">
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                <a href="{{ route('products.show', $product) }}" class="size-16 shrink-0 overflow-hidden rounded-lg bg-slate-50">
                                                    @if ($image)
                                                        <img src="{{ Storage::disk('public')->url($image->path) }}" alt="{{ $product->name }}" class="size-full object-cover">
                                                    @else
                                                        <span class="flex size-full items-center justify-center text-xs text-slate-400">Нет фото</span>
                                                    @endif
                                                </a>
                                                <a href="{{ route('products.show', $product) }}" class="line-clamp-2 font-medium text-slate-800 hover:text-brand-700">
                                                    {{ $product->name }}
                                                </a>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 font-semibold text-accent-600">
                                            {{ number_format($product->price / 100, 0, ',', ' ') }} ₽
                                        </td>
                                        <td class="px-4 py-4">
                                            <form
                                                method="POST"
                                                action="{{ route('cart.update') }}"
                                                data-cart-action="update"
                                                class="cart-qty inline-flex items-center rounded-md border border-slate-300 bg-white"
                                            >
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                <button
                                                    type="button"
                                                    data-qty-step="-1"
                                                    class="flex size-8 items-center justify-center text-slate-500 hover:bg-slate-100"
                                                    aria-label="Уменьшить количество"
                                                >−</button>
                                                <input
                                                    type="number"
                                                    name="quantity"
                                                    value="{{ $line['quantity'] }}"
                                                    min="1"
                                                    max="99"
                                                    class="w-12 border-x border-slate-300 py-1 text-center text-sm focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                                >
                                                <button
                                                    type="button"
                                                    data-qty-step="1"
                                                    class="flex size-8 items-center justify-center text-slate-500 hover:bg-slate-100"
                                                    aria-label="Увеличить количество"
                                                >+</button>
                                            </form>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-right font-semibold text-slate-900">
                                            <span data-line-total data-product="{{ $product->id }}">
                                                {{ number_format($line['line_total'] / 100, 0, ',', ' ') }} ₽
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            <form method="POST" action="{{ route('cart.remove') }}" data-cart-action="remove">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                <button
                                                    type="submit"
                                                    class="flex size-9 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                                    aria-label="Удалить товар"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="size-5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Итоговая сумма --}}
                <aside class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Ваш заказ</h2>

                    <dl class="mt-4 space-y-2 text-sm">
                        @foreach ($lines as $line)
                            <div class="flex justify-between gap-3" data-summary-row data-product="{{ $line['product']->id }}">
                                <dt class="line-clamp-1 text-slate-500">
                                    {{ $line['product']->name }} ×
                                    <span data-summary-qty>{{ $line['quantity'] }}</span>
                                </dt>
                                <dd class="whitespace-nowrap font-medium text-slate-800" data-summary-total>
                                    {{ number_format($line['line_total'] / 100, 0, ',', ' ') }} ₽
                                </dd>
                            </div>
                        @endforeach
                    </dl>

                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-500">Итого:</span>
                            <span id="cart-total" class="text-2xl font-bold text-accent-600">
                                {{ number_format($total / 100, 0, ',', ' ') }} ₽
                            </span>
                        </div>
                    </div>

                    <a
                        href="{{ route('checkout.create') }}"
                        class="mt-5 block w-full rounded-md bg-brand-600 py-3 text-center text-sm font-semibold text-white transition-colors hover:bg-brand-700"
                    >
                        Оформить заказ
                    </a>

                    <a href="{{ route('storefront') }}" class="mt-3 block text-center text-sm font-medium text-slate-600 hover:text-brand-700">
                        Продолжить покупки
                    </a>

                    <form method="POST" action="{{ route('cart.clear') }}" data-cart-action="clear" class="mt-4 text-center">
                        @csrf
                        <button type="submit" class="text-xs text-slate-400 transition-colors hover:text-red-600">
                            Очистить корзину
                        </button>
                    </form>
                </aside>
            </div>
        @endif
    </div>
</x-layouts.shop>
