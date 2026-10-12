<x-layouts.shop title="Статьи — Repa">
<div class="mx-auto max-w-4xl px-4 py-8"><h1 class="text-3xl font-bold">Статьи</h1><div class="mt-6 space-y-6">@forelse($articles as $article)<article class="rounded-xl bg-white p-6"><h2 class="text-xl font-semibold"><a href="{{ route('articles.show',$article->slug) }}">{{ $article->title }}</a></h2><p class="mt-2 text-slate-600">{{ $article->excerpt }}</p></article>@empty<p>Готовим полезные материалы о семенах и выращивании. Загляните позже.</p>@endforelse</div><div class="mt-6">{{ $articles->links() }}</div></div>
</x-layouts.shop>
