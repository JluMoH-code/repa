@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Навигация по страницам" class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
        {{-- Сводка: "Показано 1–12 из 55" --}}
        <p class="text-sm text-slate-500">
            Показано
            @if ($paginator->firstItem())
                <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-slate-700">{{ $paginator->lastItem() }}</span>
            @else
                {{ $paginator->count() }}
            @endif
            из <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span>
        </p>

        {{-- Кнопки страниц --}}
        <div class="flex items-center gap-1">
            {{-- Назад --}}
            @if ($paginator->onFirstPage())
                <span
                    aria-disabled="true"
                    aria-label="Предыдущая страница"
                    class="flex size-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-300"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </span>
            @else
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    rel="prev"
                    aria-label="Предыдущая страница"
                    class="flex size-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 transition-colors hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </a>
            @endif

            {{-- Номера страниц --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span
                        aria-disabled="true"
                        class="flex size-9 items-center justify-center text-sm text-slate-400"
                    >{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span
                                aria-current="page"
                                class="flex size-9 items-center justify-center rounded-md border border-brand-600 bg-brand-600 text-sm font-semibold text-white"
                            >{{ $page }}</span>
                        @else
                            <a
                                href="{{ $url }}"
                                aria-label="Страница {{ $page }}"
                                class="flex size-9 items-center justify-center rounded-md border border-slate-200 bg-white text-sm font-medium text-slate-600 transition-colors hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700"
                            >{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Вперёд --}}
            @if ($paginator->hasMorePages())
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    rel="next"
                    aria-label="Следующая страница"
                    class="flex size-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 transition-colors hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @else
                <span
                    aria-disabled="true"
                    aria-label="Следующая страница"
                    class="flex size-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-300"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @endif
        </div>
    </nav>
@endif
