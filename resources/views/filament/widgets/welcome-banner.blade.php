<div class="fi-section overflow-hidden rounded-xl bg-primary-600 p-6 text-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-primary-100">Панель управления</p>
            <h2 class="mt-1 text-xl font-bold">Здравствуйте, {{ auth()->user()->name }}!</h2>
            <p class="mt-1 text-sm text-primary-100">
                Управляйте каталогом, пользователями и настройками магазина.
            </p>
        </div>

        <a
            href="{{ route('storefront') }}"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-primary-700 shadow-sm transition hover:bg-primary-50"
        >
            Открыть магазин
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
        </a>
    </div>
</div>
