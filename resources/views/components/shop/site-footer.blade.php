@props(['footerCategories' => collect()])

@php
    $settings = app(\App\Actions\Settings\SettingsManager::class);
    $contactPhone = $settings->get('phone');
    $contactEmail = $settings->get('email');
    $contactAddress = $settings->get('address');
    $contactHours = $settings->get('work_hours');
@endphp

<footer class="mt-12 border-t border-slate-200 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-10">
        <div class="grid gap-8 md:grid-cols-3 lg:grid-cols-4">
            <!-- Популярные категории -->
            <div class="lg:col-span-1">
                <h3 class="mb-3 font-semibold text-slate-900">Популярные категории</h3>
                <ul class="space-y-2 text-sm text-slate-600">
                    @forelse ($footerCategories as $category)
                        <li><a href="{{ route('catalog.show', $category) }}" class="hover:text-brand-700">{{ $category->name }}</a></li>
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
                    <li><a href="#" class="hover:text-brand-700">Ассортимент</a></li>
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
                        <span>{{ $contactAddress }}</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg width="16" height="16" class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $contactPhone) }}" class="hover:text-brand-700">{{ $contactPhone }}</a>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg width="16" height="16" class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:{{ $contactEmail }}" class="hover:text-brand-700">{{ $contactEmail }}</a>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-slate-500">{{ $contactHours }}</span>
                    </li>
                </ul>
            </div>

            <!-- Карта Яндекс: метка на адресе магазина (координаты ул. Поддубного 1,
                 Волгоград; если адрес в настройках изменится — обновить и ll/pt) -->
            <div class="lg:col-span-1">
                <h3 class="mb-3 font-semibold text-slate-900">Мы на карте</h3>
                <div class="overflow-hidden rounded-lg border border-slate-200 shadow-sm">
                    <iframe 
                        src="https://yandex.ru/map-widget/v1/?ll=44.5428453%2C48.7719530&z=16&pt=44.5428453,48.7719530,pm2rdm" 
                        width="100%" 
                        height="250" 
                        frameborder="0" 
                        allowfullscreen="true"
                        class="w-full"
                        title="Карта магазина Repa"
                        loading="lazy"
                    ></iframe>
                </div>
                <div class="mt-2 text-xs text-slate-500">
                    <a href="https://yandex.ru/maps/?text={{ urlencode($contactAddress) }}" target="_blank" rel="noopener noreferrer" class="text-brand-600 hover:text-brand-700 underline">
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
