<?php

use App\Http\Controllers\CabinetController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DemoProductPageController;
use App\Http\Controllers\FavoritesController;
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
Route::get('/cart/quantities', [CartController::class, 'quantities'])->name('cart.quantities');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

Route::middleware('auth')->group(function () {
    // Личный кабинет
    Route::get('/cabinet', [CabinetController::class, 'index'])->name('cabinet.index');
    Route::get('/cabinet/profile', [CabinetController::class, 'profile'])->name('cabinet.profile');
    Route::post('/cabinet/profile', [CabinetController::class, 'updateProfile'])->name('cabinet.profile.update');
    Route::post('/cabinet/password', [CabinetController::class, 'updatePassword'])->name('cabinet.password.update');
    Route::get('/cabinet/orders', [CabinetController::class, 'orders'])->name('cabinet.orders');
    Route::get('/cabinet/favorites', [FavoritesController::class, 'index'])->name('cabinet.favorites');
});

// Избранное (AJAX с карточек; гости хранят его в сессии, как корзину)
Route::post('/favorites/toggle', [FavoritesController::class, 'toggle'])->name('favorites.toggle');
Route::post('/favorites/remove', [FavoritesController::class, 'remove'])->name('favorites.remove');

// TEMP: dev-only perf measurement helper, remove after use.
Route::get('/__dev_login', function () {
    auth()->loginUsingId(User::first()->id);

    return 'ok';
});
