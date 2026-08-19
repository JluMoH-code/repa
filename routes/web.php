<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DemoProductPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('storefront');

// Страница товара
Route::get('/product/{product}', [ProductController::class, 'show'])->name('products.show');

// Каталог: одна точка входа, тип страницы (выбор подкатегории / список товаров)
// решается контроллером по наличию подкатегорий у категории.
Route::get('/catalog/{category}', [CatalogController::class, 'show'])->name('catalog.show');

// Поиск по каталогу
Route::get('/search', [SearchController::class, 'index'])->name('search');

// Демо-страница карточки товара (СЗР) — статичные данные, см. комментарий в контроллере.
Route::get('/demo/product', [DemoProductPageController::class, 'show'])->name('demo.product');

// Корзина
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

Route::middleware('auth')->get('/home', function () {
    return view('home');
})->name('home');

// TEMP: dev-only perf measurement helper, remove after use.
Route::get('/__dev_login', function () {
    auth()->loginUsingId(User::first()->id);

    return 'ok';
});
