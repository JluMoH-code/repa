<x-layouts.shop :footer-categories="$footerCategories" :title="'Мои заказы — ' . config('app.name')">
    <div class="mx-auto max-w-7xl px-4 py-6">
        <h1 class="text-2xl font-bold text-slate-900">Личный кабинет</h1>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[260px_1fr]">
            <x-cabinet.sidebar active="orders" />

            <div class="rounded-xl border border-dashed border-slate-300 bg-white py-16 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" class="mx-auto size-16 text-slate-300">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.708 2.602-7.201.245-1.007-.44-2.049-1.4-2.049H5.106M7.5 14.25 5.106 5.25M6 18.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                </svg>
                <p class="mt-4 text-lg font-semibold text-slate-700">Заказов пока нет</p>
                <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                    Оформление заказов появится на следующем этапе. А пока — загляните в каталог и добавьте понравившиеся семена в корзину.
                </p>
                <a href="{{ route('storefront') }}" class="mt-6 inline-block rounded-md bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                    Перейти в каталог
                </a>
            </div>
        </div>
    </div>
</x-layouts.shop>
