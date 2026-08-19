<?php

namespace App\Listeners;

use App\Actions\Cart\CartManager;
use Illuminate\Auth\Events\Login;

/**
 * При успешном входе переносит гостевую (сессионную) корзину в корзину
 * пользователя. Регистрируется автоматически: Laravel сканирует app/Listeners
 * и по сигнатуре handle(Login $event) подписывает слушателя на событие
 * Illuminate\Auth\Events\Login.
 */
class MergeCartOnLogin
{
    public function __construct(private readonly CartManager $cart) {}

    public function handle(Login $event): void
    {
        $this->cart->mergeGuestCart($event->user);
    }
}
