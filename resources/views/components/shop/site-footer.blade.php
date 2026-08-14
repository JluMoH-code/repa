@props(['footerCategories' => collect()])

<footer class="mt-12 border-t border-slate-200 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-10">
        <div class="grid gap-8 md:grid-cols-3">
            <div>
                <h3 class="mb-3 font-semibold text-slate-900">Популярные категории</h3>
                <ul class="space-y-2 text-sm text-slate-600">
                    @forelse ($footerCategories as $category)
                        <li><a href="#" class="hover:text-brand-700">{{ $category->name }}</a></li>
                    @empty
                        <li class="text-slate-400">Категории появятся здесь</li>
                    @endforelse
                </ul>
            </div>

            <div>
                <h3 class="mb-3 font-semibold text-slate-900">Покупателям</h3>
                <ul class="space-y-2 text-sm text-slate-600">
                    <li><a href="#" class="hover:text-brand-700">О магазине</a></li>
                    <li><a href="#" class="hover:text-brand-700">Публикации</a></li>
                    <li><a href="#" class="hover:text-brand-700">Доставка и оплата</a></li>
                    <li><a href="#" class="hover:text-brand-700">Обмен/Возврат</a></li>
                    <li><a href="#" class="hover:text-brand-700">Контакты</a></li>
                </ul>
            </div>

            <div>
                <h3 class="mb-3 font-semibold text-slate-900">Контакты</h3>
                <ul class="space-y-2 text-sm text-slate-600">
                    <li>Адрес появится здесь</li>
                    <li><a href="tel:+70000000000" class="hover:text-brand-700">8 (800) 000-00-00</a></li>
                    <li><a href="mailto:info@example.com" class="hover:text-brand-700">info@example.com</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-8 border-t border-slate-200 pt-6 text-xs text-slate-400">
            © {{ date('Y') }} {{ config('app.name') }}. Все права защищены.
        </div>
    </div>
</footer>
