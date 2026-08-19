<x-layouts.shop :footer-categories="$footerCategories" :title="($q !== '' ? 'Поиск: '.$q : 'Поиск').' — '.config('app.name')">
    <x-shop.breadcrumbs :items="[['label' => 'Главная', 'url' => route('storefront')], ['label' => 'Поиск']]" />

    <div class="mx-auto max-w-7xl px-4 pb-12">
        <h1 class="text-2xl font-bold text-slate-900">Поиск по каталогу</h1>

        <form method="GET" action="{{ route('search') }}" class="mt-6 flex max-w-2xl items-stretch gap-2">
            <input
                type="text"
                name="q"
                value="{{ $q }}"
                placeholder="Название, артикул или штрихкод..."
                class="w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500"
            >
            <button type="submit" class="rounded-md bg-accent-500 px-5 text-sm font-medium text-white hover:bg-accent-600">
                Найти
            </button>
        </form>

        @if ($q !== '')
            <p class="mt-4 text-sm text-slate-600">
                По запросу «{{ $q }}»
                @if ($products->isEmpty())
                    ничего не найдено.
                @else
                    найдено товаров: {{ $products->total() }}.
                @endif
            </p>

            @if ($products->isEmpty())
                <div class="mt-6 rounded-xl border border-dashed border-slate-300 bg-white py-16 text-center">
                    <p class="text-slate-500">Товары не найдены</p>
                    <p class="mt-1 text-sm text-slate-400">Попробуйте изменить запрос или загляните в <a href="{{ route('storefront') }}" class="text-brand-700 hover:underline">каталог</a>.</p>
                </div>
            @else
                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                    @foreach ($products as $product)
                        <x-shop.product-card :product="$product" />
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @endif
        @endif
    </div>
</x-layouts.shop>
