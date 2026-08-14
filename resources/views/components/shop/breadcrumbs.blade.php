@props(['items'])

<nav class="mx-auto max-w-7xl px-4 py-4 text-sm">
    <ol class="flex flex-wrap items-center gap-1.5 text-slate-500">
        @foreach ($items as $index => $item)
            <li class="flex items-center gap-1.5">
                @if (!$loop->first)
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-3.5 text-slate-300">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                @endif

                @if ($loop->last)
                    <span class="font-medium text-slate-800">{{ $item['label'] }}</span>
                @else
                    <a href="{{ $item['url'] ?? '#' }}" class="hover:text-brand-700">{{ $item['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
