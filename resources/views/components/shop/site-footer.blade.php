@props(['footerCategories' => collect()])

<footer class="mt-12 border-t border-slate-200 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-10">
        <div class="grid gap-8 md:grid-cols-3 lg:grid-cols-4">
            <!-- Популярные категории -->
            <div class="lg:col-span-1">
                <h3 class="mb-3 font-semibold text-slate-900">Популярные категории</h3>
                <ul class="space-y-2 text-sm text-slate-600">
                    @forelse ($footerCategories as $category)
                        <li><a href="#" class="hover:text-brand-700">{{ $category->name }}</a></li>
                    @empty
                        <li class="text-slate-400">Категории появятся здесь</li>
                    @endforelse
                </ul>
            </div>

            <!-- Покупателям -->
            <div class="lg:col-span-1">
                <h3 class="mb-3 font-semibold text-slate-900">Покупателям</h3>
                <ul class="space-y-2 text-sm text-slate-600">
                    <li><a href="#" class="hover:text-brand-700">О магазине</a></li>
                    <li><a href="#" class="hover:text-brand-700">Публикации</a></li>
                    <li><a href="#" class="hover:text-brand-700">Доставка и оплата</a></li>
                    <li><a href="#" class="hover:text-brand-700">Обмен/Возврат</a></li>
                    <li><a href="#" class="hover:text-brand-700">Контакты</a></li>
                </ul>
            </div>

            <!-- Контакты -->
            <div class="lg:col-span-1">
                <h3 class="mb-3 font-semibold text-slate-900">Контакты</h3>
                <ul class="space-y-2 text-sm text-slate-600">
                    <li class="flex items-start gap-2">
                        <svg width="16" height="16" class="mt-0.5 h-4 w-4 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>г. Волжский, ул. Волжской военной флотилии, 64</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg width="16" height="16" class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <a href="tel:+78443000000" class="hover:text-brand-700">8 (8443) 00-00-00</a>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg width="16" height="16" class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:info@seedshop.ru" class="hover:text-brand-700">info@seedshop.ru</a>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-slate-500">Пн-Пт: 9:00 - 18:00</span>
                    </li>
                </ul>
            </div>

            <!-- Карта Яндекс -->
            <div class="lg:col-span-1">
                <h3 class="mb-3 font-semibold text-slate-900">Мы на карте</h3>
                <div class="overflow-hidden rounded-lg border border-slate-200 shadow-sm">
                    <iframe 
                        src="https://yandex.ru/map-widget/v1/?ll=44.786693%2C48.783593&mode=search&ol=geo&ouri=ymapsbm1%3A%2F%2Fgeo%3Fdata%3DCgg1NjQwNTIyMBJc0KDQvtGB0YHQuNGPLCDQnNC-0YHQutC-0LLQviwg1L7QsdC70LDRgdGC0YwsINCf0L7QtNCy0LXRgNC-0LLQviwg0L_QvtC80L7RgdC_0LzQtdC90LrQsNGG0LAsIDY0IgoNWLkzVUIcXEJC&amp;z=16.61" 
                        width="100%" 
                        height="250" 
                        frameborder="0" 
                        allowfullscreen="true"
                        class="w-full"
                        title="Карта магазина Seed Shop"
                        loading="lazy"
                    ></iframe>
                </div>
                <div class="mt-2 text-xs text-slate-500">
                    <a href="https://yandex.ru/maps/-/CDu~eKqJ" target="_blank" rel="noopener noreferrer" class="text-brand-600 hover:text-brand-700 underline">
                        Открыть в Яндекс.Картах →
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-8 border-t border-slate-200 pt-6 text-xs text-slate-400">
            © {{ date('Y') }} {{ config('app.name') }}. Все права защищены.
        </div>
    </div>
</footer>
