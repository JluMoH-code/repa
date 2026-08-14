<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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

    @livewireScripts
</body>
</html>
