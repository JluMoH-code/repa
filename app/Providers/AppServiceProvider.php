<?php

namespace App\Providers;

use App\Actions\Cart\CartManager;
use App\Actions\Favorites\FavoriteManager;
use App\Actions\Orders\OrderManager;
use App\Actions\Settings\SettingsManager;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisterResponse;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton'ы: менеджеры кэшируют данные на время запроса,
        // чтобы карточки/шапка не делали N+1 запросов.
        $this->app->singleton(CartManager::class);
        $this->app->singleton(FavoriteManager::class);
        $this->app->singleton(OrderManager::class);
        $this->app->singleton(SettingsManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Редирект после входа/регистрации: админ → админка, покупатель → кабинет.
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);

        // Единый стиль пагинации витрины (белые кнопки, зелёная активная
        // страница) — вместо тёмно-серой пагинации Tailwind по умолчанию.
        Paginator::defaultView('pagination.shop');
    }
}
