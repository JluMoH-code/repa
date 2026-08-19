<x-layouts.shop :footer-categories="$footerCategories" :title="'Личный кабинет — ' . config('app.name')">
    <div class="mx-auto max-w-7xl px-4 py-6">
        <h1 class="text-2xl font-bold text-slate-900">Личный кабинет</h1>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[260px_1fr]">
            <x-cabinet.sidebar active="index" />

            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Здравствуйте, {{ auth()->user()->name }}!
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Здесь вы можете управлять своим профилем, следить за заказами и сохранять понравившиеся товары.
                </p>

                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <a href="{{ route('cabinet.favorites') }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md">
                        <span class="flex size-10 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                            </svg>
                        </span>
                        <span class="mt-3 block text-2xl font-bold text-slate-900">{{ $favoritesCount }}</span>
                        <span class="text-sm text-slate-500">в избранном</span>
                        <span class="mt-1 block text-xs font-medium text-brand-700 group-hover:underline">Перейти</span>
                    </a>

                    <a href="{{ route('cart.index') }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md">
                        <span class="flex size-10 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.708 2.602-7.201.245-1.007-.44-2.049-1.4-2.049H5.106M7.5 14.25 5.106 5.25M6 18.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                            </svg>
                        </span>
                        <span class="mt-3 block text-2xl font-bold text-slate-900">{{ $cartCount }}</span>
                        <span class="text-sm text-slate-500">товаров в корзине</span>
                        <span class="mt-1 block text-xs font-medium text-brand-700 group-hover:underline">Перейти</span>
                    </a>

                    <a href="{{ route('cabinet.profile') }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md">
                        <span class="flex size-10 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </span>
                        <span class="mt-3 block text-lg font-bold text-slate-900">Профиль</span>
                        <span class="text-sm text-slate-500">данные и пароль</span>
                        <span class="mt-1 block text-xs font-medium text-brand-700 group-hover:underline">Перейти</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.shop>
