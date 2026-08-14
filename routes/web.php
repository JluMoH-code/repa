<?php

use App\Http\Controllers\DemoProductPageController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('storefront');

// Страница товара
Route::get('/product/{product}', [App\Http\Controllers\ProductController::class, 'show'])->name('products.show');

// Демо-страница карточки товара (СЗР) — статичные данные, см. комментарий в контроллере.
Route::get('/demo/product', [DemoProductPageController::class, 'show'])->name('demo.product');

Route::middleware('auth')->get('/home', function () {
    return view('home');
})->name('home');

// TEMP: dev-only perf measurement helper, remove after use.
Route::get('/__dev_login', function () {
    auth()->loginUsingId(\App\Models\User::first()->id);

    return 'ok';
});
