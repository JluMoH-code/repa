<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;

class ContentController extends Controller
{
    public function index()
    {
        return view('content.index', ['articles' => ContentPage::published()->where('kind', 'article')->latest('published_at')->paginate(12)]);
    }

    public function article(string $slug)
    {
        return $this->show($slug, 'article');
    }

    public function page(string $slug)
    {
        return $this->show($slug, 'page');
    }

    private function show(string $slug, string $kind)
    {
        return view('content.show', ['page' => ContentPage::published()->where('kind', $kind)->where('slug', $slug)->firstOrFail()]);
    }
}
