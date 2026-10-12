<?php

namespace App\Http\Controllers;

use App\Actions\Settings\SettingsManager;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::query()->orderBy('sort_order')->orderBy('name')->get();

        $bestsellers = Product::query()->visible()->where('is_bestseller', true)
            ->with(['images', 'variants'])->latest()->limit(12)->get();

        $newArrivals = Product::query()->visible()
            ->with(['images', 'variants'])->latest()->limit(12)->get();

        $recommended = Product::query()->visible()->where('is_recommended', true)
            ->with(['images', 'variants'])->latest()->limit(12)->get();

        $slides = [
            [
                'eyebrow' => 'Каталог семян',
                'title' => 'Готовимся к новому сезону',
                'subtitle' => 'Овощи, зелень и цветы для сада и огорода',
                'theme' => 'brand',
            ],
            [
                'eyebrow' => 'Ассортимент растёт',
                'title' => 'Каталог пополняется каждую неделю',
                'subtitle' => 'Следите за новинками в разделе «Новинки»',
                'theme' => 'brand',
            ],
        ];

        $configuredSlides = Banner::where('is_active', true)->orderBy('sort_order')->get();
        if ($configuredSlides->isNotEmpty()) {
            $slides = $configuredSlides->map(fn ($banner) => ['eyebrow' => 'Repa', 'title' => $banner->title, 'subtitle' => $banner->subtitle, 'image' => $banner->image, 'url' => $banner->url, 'theme' => 'brand'])->all();
        }

        return view('storefront', [
            'categories' => $categories,
            'bestsellers' => $bestsellers,
            'newArrivals' => $newArrivals,
            'recommended' => $recommended,
            'slides' => $slides,
            'homeIntro' => app(SettingsManager::class)->get('home_intro', ''),
        ]);
    }
}
