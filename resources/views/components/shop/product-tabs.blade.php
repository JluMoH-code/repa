@props(['tabs'])

<div x-data="{ active: 0 }" class="rounded-xl border border-slate-200 bg-white">
    <div class="flex flex-wrap gap-2 border-b border-slate-200 p-3">
        @foreach ($tabs as $index => $tab)
            <button
                @click="active = {{ $index }}"
                type="button"
                class="rounded-md px-4 py-2 text-sm font-medium transition-colors"
                :class="active === {{ $index }} ? 'bg-accent-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            >
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    @foreach ($tabs as $index => $tab)
        <div x-show="active === {{ $index }}" class="p-5 md:p-6" style="{{ $index === 0 ? '' : 'display: none;' }}">
            {!! $tab['content'] !!}
        </div>
    @endforeach
</div>
