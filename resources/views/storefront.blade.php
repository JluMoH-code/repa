<x-layouts.shop :footer-categories="$categories->whereNull('parent_id')->take(6)">
    <div class="mx-auto max-w-7xl px-4 py-6">
        <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
            <aside class="hidden lg:block">
                <x-shop.category-menu :categories="$categories" />
            </aside>

            <div>
                <x-shop.hero-banner :slides="$slides" />
            </div>
        </div>

        <div class="mt-10 space-y-10">
            <x-shop.product-carousel title="Хиты продаж" :products="$bestsellers" />
            <x-shop.product-carousel title="Новинки" :products="$newArrivals" />
            <x-shop.product-carousel title="Рекомендуем" :products="$recommended" />

            <x-shop.feature-badges />
            <x-shop.about-block />
        </div>
    </div>
</x-layouts.shop>
