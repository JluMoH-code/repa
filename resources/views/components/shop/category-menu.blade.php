@props(['categories'])

<div x-data="{ openId: null }" class="rounded-xl border border-slate-200 bg-white">
    <div class="flex items-center gap-2 rounded-t-xl bg-brand-600 px-4 py-3 text-white">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
        <span class="font-semibold">Каталог</span>
    </div>

    <ul class="divide-y divide-slate-100 py-1">
        @foreach ($categories->where('parent_id', null) as $root)
            @php $children = $categories->where('parent_id', $root->id); @endphp
            <li x-data="{ open: false }">
                <div
                    @if ($children->isNotEmpty()) @click="open = !open" @endif
                    class="flex cursor-pointer items-center justify-between px-4 py-2.5 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-700"
                >
                    <a href="{{ route('catalog.show', $root) }}" class="flex-1" @if ($children->isNotEmpty()) @click.stop @endif>{{ $root->name }}</a>

                    @if ($children->isNotEmpty())
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            class="size-4 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-90': open }">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    @endif
                </div>

                @if ($children->isNotEmpty())
                    <ul x-show="open" x-transition class="bg-slate-50 pb-1" style="display: none;">
                        @foreach ($children as $child)
                            <li>
                                <a href="{{ route('catalog.show', $child) }}" class="block px-8 py-2 text-sm text-slate-600 hover:text-brand-700">
                                    {{ $child->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</div>
