<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function show(Request $request, Category $category)
    {
        if (! $category->is_active) {
            abort(404);
        }

        $category->load('children');
        $showAll = $request->boolean('all');

        // Тип 1: у категории есть подкатегории и не запрошен режим "Все" —
        // страница выбора подкатегории.
        if ($category->children->isNotEmpty() && ! $showAll) {
            return $this->subcategoryPicker($category);
        }

        // Тип 2: листовая категория (или явно запрошено "Все" по родительской) —
        // каталог товаров.
        return $this->productListing($request, $category, $showAll);
    }

    private function subcategoryPicker(Category $category)
    {
        $children = $category->children->map(function (Category $child) {
            $child->setAttribute('products_count', $child->products()->visible()->count());

            return $child;
        });

        $footerCategories = Category::query()->whereNull('parent_id')->orderBy('sort_order')->limit(6)->get();

        return view('catalog.subcategories', [
            'category' => $category,
            'children' => $children,
            'breadcrumbs' => [
                ['label' => 'Главная', 'url' => route('storefront')],
                ['label' => $category->name],
            ],
            'footerCategories' => $footerCategories,
        ]);
    }

    private function productListing(Request $request, Category $category, bool $showAll)
    {
        // Для фильтров/цены группа привязана к КОРНЕВОЙ категории: если мы на
        // подкатегории — берём фильтры у родителя, если на корневой — у неё самой.
        $rootCategory = $category->parent_id ? $category->parent : $category;

        $categoryIds = $showAll ? $category->selfAndChildrenIds() : [$category->id];

        $baseQuery = Product::query()->visible()->whereIn('category_id', $categoryIds);

        // Границы цены считаем ДО применения фильтра по цене (иначе слайдер
        // "сожмётся" сам под себя после первого же выбора).
        $priceBounds = (clone $baseQuery)->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();
        $priceMin = (int) ($priceBounds->min_price ?? 0);
        $priceMax = (int) ($priceBounds->max_price ?? 0);

        $query = clone $baseQuery;

        if ($request->boolean('stock')) {
            $query->where('in_stock', true);
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', (int) round($request->float('price_min') * 100));
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', (int) round($request->float('price_max') * 100));
        }

        $selectedFilters = (array) $request->input('filter', []);

        foreach ($selectedFilters as $groupSlug => $valueSlugs) {
            $valueSlugs = array_filter((array) $valueSlugs);

            if (empty($valueSlugs)) {
                continue;
            }

            // Внутри одной группы значения объединяются через ИЛИ (товар подходит,
            // если у него есть хотя бы одно из выбранных значений); между разными
            // группами — через И (каждая group добавляет свой whereHas в цепочку).
            $query->whereHas('filterValues', function ($q) use ($groupSlug, $valueSlugs) {
                $q->whereHas('group', fn ($g) => $g->where('slug', $groupSlug))
                    ->whereIn('slug', $valueSlugs);
            });
        }

        $sort = $request->input('sort', 'default');

        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name' => $query->orderBy('name'),
            'stock' => $query->orderByDesc('in_stock')->orderBy('name'),
            default => $query->orderByDesc('in_stock')->orderByDesc('created_at'),
        };

        $perPage = (int) $request->input('per_page', 24);
        $perPage = in_array($perPage, [12, 24, 48], true) ? $perPage : 24;

        $products = $query->with(['images', 'variants', 'manufacturer'])
            ->paginate($perPage)
            ->withQueryString();

        // Динамические группы фильтров текущей категории + счётчики товаров
        // (считаем по базовому запросу категории с учётом наличия/цены, но БЕЗ
        // учёта других выбранных значений фильтров — упрощённая, не полностью
        // фасетная модель подсчёта).
        $countsScope = clone $baseQuery;
        if ($request->boolean('stock')) {
            $countsScope->where('in_stock', true);
        }

        $filterGroups = $rootCategory->filterGroups()
            ->with(['values' => function ($q) use ($countsScope) {
                $q->withCount(['products' => function ($pq) use ($countsScope) {
                    $pq->whereIn('products.id', $countsScope->select('products.id'));
                }]);
            }])
            ->get();

        $breadcrumbs = [['label' => 'Главная', 'url' => route('storefront')]];

        if ($category->parent_id) {
            $breadcrumbs[] = ['label' => $rootCategory->name, 'url' => route('catalog.show', $rootCategory)];
            $breadcrumbs[] = ['label' => $category->name];
        } else {
            $breadcrumbs[] = ['label' => $category->name.($showAll ? ' — все товары' : '')];
        }

        $footerCategories = Category::query()->whereNull('parent_id')->orderBy('sort_order')->limit(6)->get();

        return view('catalog.products', [
            'category' => $category,
            'rootCategory' => $rootCategory,
            'showAll' => $showAll,
            'products' => $products,
            'filterGroups' => $filterGroups,
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
            'breadcrumbs' => $breadcrumbs,
            'footerCategories' => $footerCategories,
        ]);
    }
}
