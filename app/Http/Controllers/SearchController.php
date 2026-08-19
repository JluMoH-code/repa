<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Публичный поиск по каталогу: /search?q=...
     * Ищет по названию, SKU, штрихкоду и краткому описанию опубликованных товаров.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $products = collect();

        if ($q !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';

            $products = Product::query()
                ->visible()
                ->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like)
                        ->orWhere('barcode', 'like', $like)
                        ->orWhere('short_description', 'like', $like);
                })
                ->with(['images', 'variants', 'manufacturer'])
                ->orderByDesc('created_at')
                ->paginate(24)
                ->withQueryString();
        }

        $footerCategories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        return view('search', [
            'q' => $q,
            'products' => $products,
            'footerCategories' => $footerCategories,
        ]);
    }
}
