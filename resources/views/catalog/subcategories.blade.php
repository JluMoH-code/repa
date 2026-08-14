<x-layouts.shop :footer-categories="$footerCategories" :title="$category->name.' — '.config('app.name')">
    <x-shop.breadcrumbs :items="$breadcrumbs" />

    <div class="mx-auto max-w-7xl px-4 pb-12">
        <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
            {{-- Сайдбар: "Просмотреть по" + аккордеон "Категория" --}}
            <aside x-data="{ open: true }" class="hidden lg:block">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <h2 class="mb-3 text-sm font-semibold text-slate-900">Просмотреть по</h2>

                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-sm font-medium text-slate-700">
                        Категория
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            class="size-4 text-slate-400 transition-transform" :class="{ 'rotate-180': open }">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <ul x-show="open" x-transition class="mt-3 space-y-1">
                        @foreach ($children as $child)
                            <li>
                                <a href="{{ route('catalog.show', $child) }}" class="flex items-center justify-between rounded-md px-2 py-1.5 text-sm text-slate-600 hover:bg-brand-50 hover:text-brand-700">
                                    <span>{{ $child->name }}</span>
                                    <span class="text-xs text-slate-400">{{ $child->products_count }}</span>
                                </a>
                            </li>
                        @endforeach
                        <li>
                            <a href="{{ route('catalog.show', ['category' => $category, 'all' => 1]) }}" class="flex items-center rounded-md px-2 py-1.5 text-sm font-medium text-brand-700 hover:bg-brand-50">
                                Все
                            </a>
                        </li>
                    </ul>
                </div>
            </aside>

            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $category->name }}</h1>

                @if ($category->image)
                    <div class="mt-4 overflow-hidden rounded-xl">
                        <img src="{{ Storage::disk('public')->url($category->image) }}" alt="{{ $category->name }}" class="w-full object-cover">
                    </div>
                @endif

                @if ($category->description)
                    <p class="mt-4 text-sm text-slate-600">{{ $category->description }}</p>
                @endif

                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach ($children as $child)
                        <a href="{{ route('catalog.show', $child) }}" class="group overflow-hidden rounded-xl border border-slate-200 bg-white transition-shadow hover:shadow-md">
                            <div class="flex aspect-[4/3] items-center justify-center overflow-hidden bg-slate-50">
                                @if ($child->image)
                                    <img src="{{ Storage::disk('public')->url($child->image) }}" alt="{{ $child->name }}" class="size-full object-cover transition-transform group-hover:scale-105">
                                @else
                                    <span class="text-xs text-slate-400">Нет фото</span>
                                @endif
                            </div>
                            <div class="p-3 text-center">
                                <span class="text-sm font-semibold tracking-wide text-slate-800 uppercase">{{ $child->name }}</span>
                                <span class="block text-xs text-slate-400">{{ $child->products_count }} товаров</span>
                            </div>
                        </a>
                    @endforeach

                    <a href="{{ route('catalog.show', ['category' => $category, 'all' => 1]) }}" class="flex items-center justify-center rounded-xl border-2 border-dashed border-brand-300 bg-brand-50 p-6 text-center transition-colors hover:bg-brand-100">
                        <span class="text-sm font-semibold tracking-wide text-brand-700 uppercase">Все товары категории →</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.shop>
