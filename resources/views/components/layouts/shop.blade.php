<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <x-shop.top-bar />
    <x-shop.site-header />

    <main>
        {{ $slot }}
    </main>

    <x-shop.site-footer :footer-categories="$footerCategories ?? collect()" />

    {{-- Контейнер для toast-уведомлений (добавление в корзину и т.д.).
         Снизу справа; тостов одновременно не бывает больше одного — новый
         заменяет текущий (см. toast() в resources/js/cart.js). --}}
    <div id="toast-container" class="pointer-events-none fixed right-4 bottom-4 z-50 flex flex-col items-end gap-2"></div>

    @livewireScripts
</body>
</html>
