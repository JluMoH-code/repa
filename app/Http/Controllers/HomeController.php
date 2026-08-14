<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::query()->orderBy('sort_order')->orderBy('name')->get();

        $bestsellers = Product::query()->visible()->where('is_bestseller', true)
            ->with('images')->latest()->limit(12)->get();

        $newArrivals = Product::query()->visible()
            ->with('images')->latest()->limit(12)->get();

        $recommended = Product::query()->visible()->where('is_recommended', true)
            ->with('images')->latest()->limit(12)->get();

        $slides = [
            [
                'eyebrow' => 'Каталог семян',
                'title' => 'Готовимся к новому сезону',
                'subtitle' => 'Овощи, зелень и цветы для сада и огорода',
                'class' => 'bg-gradient-to-r from-brand-600 to-brand-500',
            ],
            [
                'eyebrow' => 'Ассортимент растёт',
                'title' => 'Каталог пополняется каждую неделю',
                'subtitle' => 'Следите за новинками в разделе «Новинки»',
                'class' => 'bg-gradient-to-r from-accent-600 to-accent-500',
            ],
        ];

        return view('storefront', [
            'categories' => $categories,
            'bestsellers' => $bestsellers,
            'newArrivals' => $newArrivals,
            'recommended' => $recommended,
            'slides' => $slides,
        ]);
    }
}
