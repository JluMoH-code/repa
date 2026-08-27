<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

            // Поиск без учёта регистра: в Postgres (боевой) LIKE регистрозависим
            // (и для кириллицы тоже) — используем ILIKE; в SQLite (тесты) отдельного
            // ILIKE нет, а LIKE для ASCII уже нечувствителен к регистру.
            $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            $products = Product::query()
                ->visible()
                ->where(function ($query) use ($like, $operator) {
                    $query->where('name', $operator, $like)
                        ->orWhere('sku', $operator, $like)
                        ->orWhere('barcode', $operator, $like)
                        ->orWhere('short_description', $operator, $like);
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
