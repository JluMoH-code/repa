<x-layouts.shop :title="$page->seo_title ?: $page->title">
<x-slot name="description">{{ $page->seo_description ?: $page->excerpt }}</x-slot>
<article class="mx-auto max-w-3xl px-4 py-8"><h1 class="text-3xl font-bold">{{ $page->title }}</h1>@if($page->excerpt)<p class="mt-4 text-lg text-slate-600">{{ $page->excerpt }}</p>@endif<div class="mt-6 space-y-4">@foreach(preg_split('/\R\s*\R/u',$page->body) as $paragraph)<p class="whitespace-pre-line leading-relaxed">{{ $paragraph }}</p>@endforeach</div></article>
</x-layouts.shop>
