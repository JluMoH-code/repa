<header class="border-b border-slate-100 bg-white">
    @php
        $cartCount = app(\App\Actions\Cart\CartManager::class)->count();
        $favoritesCount = app(\App\Actions\Favorites\FavoriteManager::class)->count();
    @endphp
    <div class="mx-auto flex max-w-7xl items-center gap-6 px-4 py-4">
        <a href="{{ url('/') }}" class="flex items-center gap-2 shrink-0">
            <span class="flex size-9 items-center justify-center rounded-lg bg-brand-600 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-3 3-3 7-3 7s4 0 7-3c1-2.5-1-5-4-4Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v11M8 21h8" />
                </svg>
            </span>
            <span class="text-xl font-bold tracking-tight text-slate-900">
                {{ config('app.name') }}
            </span>
        </a>

        <form action="{{ route('search') }}" method="GET" class="hidden flex-1 items-stretch md:flex">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Введите запрос..."
                class="w-full rounded-l-md border border-slate-300 border-r-0 px-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500"
            >
            <button type="submit" class="rounded-r-md bg-accent-500 px-4 text-white hover:bg-accent-600" aria-label="Найти">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </button>
        </form>

        <div class="ml-auto flex items-center gap-3 shrink-0 text-sm">
            <a href="#" class="hidden items-center gap-1.5 rounded-md border border-slate-200 px-3 py-2 text-slate-600 hover:border-brand-300 hover:text-brand-700 lg:flex">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5M21 16.5 16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                </svg>
                Сравнить (0)
            </a>
            <a href="{{ route('cabinet.favorites') }}" class="relative hidden items-center gap-1.5 rounded-md border border-slate-200 px-3 py-2 text-slate-600 hover:border-brand-300 hover:text-brand-700 lg:flex">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
                Избранное
                <span id="favorites-count" class="rounded-full bg-accent-500 px-1.5 py-0.5 text-[11px] font-semibold text-white">{{ $favoritesCount }}</span>
            </a>
            @auth
                <a href="{{ route('cabinet.index') }}" class="flex items-center gap-2 rounded-md bg-slate-800 py-2 pr-3 pl-2 text-white hover:bg-slate-700" aria-label="Личный кабинет">
                    <span class="flex size-6 items-center justify-center rounded-full bg-white/15 text-xs font-bold uppercase">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                    <span class="hidden max-w-28 truncate font-medium lg:inline">{{ auth()->user()->name }}</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="flex size-10 items-center justify-center rounded-md bg-slate-800 text-white hover:bg-slate-700" aria-label="Личный кабинет">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </a>
            @endauth
            <a href="{{ route('cart.index') }}" class="relative flex items-center gap-2 text-slate-700 hover:text-brand-700" aria-label="Корзина">
                <span class="relative flex size-10 items-center justify-center rounded-md border border-slate-200">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.708 2.602-7.201.245-1.007-.44-2.049-1.4-2.049H5.106M7.5 14.25 5.106 5.25M6 18.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                    <span id="cart-count" class="absolute -top-1.5 -right-1.5 flex size-5 items-center justify-center rounded-full bg-accent-500 text-xs font-semibold text-white">{{ $cartCount }}</span>
                </span>
                <span class="hidden font-medium lg:inline">Корзина</span>
            </a>
        </div>
    </div>
</header>
