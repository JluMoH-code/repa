<?php

namespace App\Providers;

use App\Actions\Favorites\FavoriteManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton: FavoriteManager кэширует ID избранного на время запроса,
        // чтобы карточки товаров не делали по запросу на каждую.
        $this->app->singleton(FavoriteManager::class);
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
