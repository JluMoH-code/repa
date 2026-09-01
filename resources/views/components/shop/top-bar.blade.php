<div class="border-b border-slate-100 bg-white text-sm">
    @php
        $contactPhone = app(\App\Actions\Settings\SettingsManager::class)->get('phone');
    @endphp
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2.5">
        <nav class="hidden items-center gap-5 text-slate-600 md:flex">
            <a href="#" class="hover:text-brand-700">О нас</a>
            <a href="#" class="hover:text-brand-700">Контакты</a>
            <a href="#" class="hover:text-brand-700">Ассортимент</a>
            <a href="#" class="hover:text-brand-700">Блог</a>
            <a href="#" class="hover:text-brand-700">Публикации</a>
        </nav>

        <div class="flex items-center gap-4 text-slate-700">
            <a href="tel:{{ preg_replace('/[^\d+]/', '', $contactPhone) }}" class="flex items-center gap-1.5 font-medium hover:text-brand-700">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106a1.125 1.125 0 0 0-1.173.417l-.97 1.293a11.25 11.25 0 0 1-6.323-6.323l1.293-.97a1.125 1.125 0 0 0 .417-1.173L8.963 3.102a1.125 1.125 0 0 0-1.091-.852H6.5A2.25 2.25 0 0 0 4.25 4.5v.75" />
                </svg>
                {{ $contactPhone }}
            </a>
        </div>
    </div>
</div>
