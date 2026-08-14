<x-layouts.shop :footer-categories="$footerCategories" :title="$category->name.' — '.config('app.name')">
    <x-shop.breadcrumbs :items="$breadcrumbs" />

    <div class="mx-auto max-w-7xl px-4 pb-12" x-data="{ mobileFiltersOpen: false, view: 'grid' }">
        <h1 class="text-2xl font-bold text-slate-900">{{ $category->name }}{{ $showAll ? ' — все товары' : '' }}</h1>

        @if ($category->image)
            <div class="mt-4 overflow-hidden rounded-xl">
                <img src="{{ Storage::disk('public')->url($category->image) }}" alt="{{ $category->name }}" class="max-h-56 w-full object-cover">
            </div>
        @endif

        <form method="GET" action="{{ url()->current() }}" class="mt-6 grid gap-6 lg:grid-cols-[260px_1fr]">
            {{-- Сброс страницы при любом изменении фильтров/сортировки --}}
            <input type="hidden" name="page" value="1">

            {{-- Сайдбар фильтров: desktop --}}
            <aside class="hidden lg:block">
                <x-shop.catalog-filters :filter-groups="$filterGroups" :price-min="$priceMin" :price-max="$priceMax" />
            </aside>

            {{-- Мобильная панель фильтров --}}
            <div
                x-show="mobileFiltersOpen"
                x-transition
                class="fixed inset-0 z-40 lg:hidden"
                style="display: none;"
            >
                <div class="absolute inset-0 bg-slate-900/50" @click="mobileFiltersOpen = false"></div>
                <div class="absolute inset-y-0 left-0 w-80 max-w-[85vw] overflow-y-auto bg-slate-50 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="font-semibold text-slate-900">Фильтры</span>
                        <button type="button" @click="mobileFiltersOpen = false" class="text-slate-500">✕</button>
                    </div>
                    <x-shop.catalog-filters :filter-groups="$filterGroups" :price-min="$priceMin" :price-max="$priceMax" />
                </div>
            </div>

            <div>
                {{-- Панель сортировки / вида / кнопка фильтров на мобильных --}}
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <button
                        type="button"
                        @click="mobileFiltersOpen = true"
                        class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 lg:hidden"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0H12" />
                        </svg>
                        Фильтры
                    </button>

                    <div class="ml-auto flex items-center gap-3">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            Сортировка:
                            <select name="sort" onchange="this.form.submit()" class="rounded-md border border-slate-300 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
                                <option value="default" @selected(request('sort', 'default') === 'default')>По умолчанию</option>
                                <option value="price_asc" @selected(request('sort') === 'price_asc')>Цена: по возрастанию</option>
                                <option value="price_desc" @selected(request('sort') === 'price_desc')>Цена: по убыванию</option>
                                <option value="name" @selected(request('sort') === 'name')>По названию</option>
                                <option value="stock" @selected(request('sort') === 'stock')>Сначала в наличии</option>
                            </select>
                        </label>

                        <div class="flex overflow-hidden rounded-md border border-slate-200">
                            <button type="button" @click="view = 'grid'" :class="view === 'grid' ? 'bg-brand-600 text-white' : 'bg-white text-slate-500'" class="p-2" aria-label="Плитка">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                </svg>
                            </button>
                            <button type="button" @click="view = 'list'" :class="view === 'list' ? 'bg-brand-600 text-white' : 'bg-white text-slate-500'" class="p-2" aria-label="Список">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                @if ($products->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white py-16 text-center">
                        <p class="text-slate-500">Товары не найдены</p>
                        <p class="mt-1 text-sm text-slate-400">Попробуйте изменить фильтры или сбросить их.</p>
                        <a href="{{ url()->current() }}" class="mt-4 inline-block text-sm font-medium text-brand-700 hover:underline">Сбросить фильтры</a>
                    </div>
                @else
                    <div
                        class="grid gap-4"
                        :class="view === 'grid' ? 'grid-cols-2 sm:grid-cols-3 xl:grid-cols-4' : 'grid-cols-1'"
                    >
                        @foreach ($products as $product)
                            <x-shop.product-card :product="$product" />
                        @endforeach
                    </div>

                    <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            Выводить:
                            <select name="per_page" onchange="this.form.submit()" class="rounded-md border border-slate-300 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
                                <option value="12" @selected($products->perPage() === 12)>12</option>
                                <option value="24" @selected($products->perPage() === 24)>24</option>
                                <option value="48" @selected($products->perPage() === 48)>48</option>
                            </select>
                        </label>

                        <div class="text-sm">
                            {{ $products->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </form>
    </div>
</x-layouts.shop>
