<?php

namespace App\Listeners;

use App\Actions\Cart\CartManager;
use App\Actions\Favorites\FavoriteManager;
use Illuminate\Auth\Events\Login;

/**
 * При успешном входе переносит гостевые данные (корзину и избранное)
 * в корзину/избранное пользователя. Регистрируется автоматически: Laravel
 * сканирует app/Listeners и по сигнатуре handle(Login $event) подписывает
 * слушателя на событие Illuminate\Auth\Events\Login.
 */
class MergeGuestDataOnLogin
{
    public function __construct(
        private readonly CartManager $cart,
        private readonly FavoriteManager $favorites,
    ) {}

    public function handle(Login $event): void
    {
        $this->cart->mergeGuestCart($event->user);
        $this->favorites->mergeGuestFavorites($event->user);
    }
}
