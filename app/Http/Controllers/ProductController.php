<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show(Request $request, Product $product)
    {
        // Публично доступны только опубликованные и активные товары.
        if ($product->status->value !== 'published' || ! $product->is_active) {
            abort(404);
        }

        $product->load(['images', 'category', 'manufacturer', 'variants']);

        $footerCategories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $breadcrumbs = [
            ['label' => 'Главная', 'url' => route('storefront')],
        ];

        if ($product->category) {
            $crumbs = $product->category->breadcrumbs();

            // Все кроме последнего
            foreach ($crumbs->slice(0, count($crumbs) - 1) as $crumb) {
                $breadcrumbs[] = ['label' => $crumb->name, 'url' => route('catalog.show', $crumb)];
            }

            // Последний элемент отдельно
            $breadcrumbs[] = ['label' => $crumbs->last()->name, 'url' => route('catalog.show', $crumbs->last())];
        }

        $breadcrumbs[] = ['label' => $product->name];

        $galleryImages = $product->images->isNotEmpty()
            ? $product->images->map(fn ($image) => [
                'src' => \Illuminate\Support\Facades\Storage::disk('public')->url($image->path),
                'label' => $product->name,
            ])->all()
            : [['src' => asset('storage/products/placeholders/1.svg'), 'label' => $product->name]];

        $characteristics = array_filter([
            $product->category ? ['label' => 'Категория', 'value' => $product->category->name] : null,
            $product->manufacturer ? ['label' => 'Производитель', 'value' => $product->manufacturer->name] : null,
            $product->culture ? ['label' => 'Культура', 'value' => $product->culture, 'highlight' => true] : null,
            $product->ripening ? ['label' => 'Срок созревания', 'value' => $product->ripening->getLabel()] : null,
            $product->growing_place ? ['label' => 'Назначение', 'value' => $product->growing_place->getLabel()] : null,
            $product->is_hybrid !== null ? ['label' => 'Гибрид', 'value' => $product->is_hybrid ? 'Да (F1)' : 'Нет'] : null,
            $product->series ? ['label' => 'Серия', 'value' => $product->series] : null,
            $product->seed_count ? ['label' => 'Семян в упаковке', 'value' => $product->seed_count.' шт.'] : null,
            ['label' => 'Артикул', 'value' => $product->sku],
        ]);

        $extraAttributes = collect($product->attributes ?? [])
            ->map(fn ($value, $key) => ['label' => $key, 'value' => $value])
            ->values();

        $similarProducts = Product::query()
            ->visible()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->with('images')
            ->inRandomOrder()
            ->limit(6)
            ->get()
            ->map(fn (Product $p) => [
                'src' => optional($p->images->firstWhere('is_main', true) ?? $p->images->first())
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url(($p->images->firstWhere('is_main', true) ?? $p->images->first())->path)
                    : asset('storage/products/placeholders/1.svg'),
                'name' => $p->name,
                'url' => route('products.show', $p),
            ])
            ->all();

        $descriptionHtml = $product->description
            ? '<div class="space-y-3 text-sm leading-relaxed text-slate-600">'.nl2br(e($product->description)).'</div>'
            : '<p class="text-sm text-slate-400">Описание пока не заполнено.</p>';

        $attributesHtml = $extraAttributes->isNotEmpty()
            ? '<dl class="grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2">'.$extraAttributes->map(
                fn ($attr) => '<div class="flex justify-between gap-4 border-b border-slate-100 py-1.5">'
                    .'<dt class="text-slate-500">'.e($attr['label']).'</dt>'
                    .'<dt class="font-medium text-slate-800">'.e($attr['value']).'</dt>'
                    .'</div>'
            )->implode('').'</dl>'
            : '<p class="text-sm text-slate-400">Дополнительные характеристики не указаны.</p>';

        $tabs = [
            ['label' => 'Описание', 'content' => $descriptionHtml],
            ['label' => 'Характеристики', 'content' => $attributesHtml],
            ['label' => 'Отзывы', 'content' => '<p class="text-sm text-slate-400">Отзывов пока нет.</p>'],
            ['label' => 'Доставка', 'content' => '<p class="text-sm text-slate-500">Доставка по всей России, подробности — на странице «Доставка и оплата».</p>'],
        ];

        return view('product-show', [
            'product' => $product,
            'footerCategories' => $footerCategories,
            'breadcrumbs' => $breadcrumbs,
            'galleryImages' => $galleryImages,
            'characteristics' => $characteristics,
            'similarProducts' => $similarProducts,
            'tabs' => $tabs,
        ]);
    }
}
