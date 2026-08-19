<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Единый стиль пагинации витрины (белые кнопки, зелёная активная
        // страница) — вместо тёмно-серой пагинации Tailwind по умолчанию.
        Paginator::defaultView('pagination.shop');
    }
}
