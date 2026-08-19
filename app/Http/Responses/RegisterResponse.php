<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

/**
 * Редирект после регистрации: новые пользователи — всегда покупатели,
 * попадают в личный кабинет.
 */
class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        return redirect()->intended(config('fortify.home'));
    }
}
