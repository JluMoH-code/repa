<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/**
 * Редирект после входа: администратор — в админку, покупатель — в кабинет.
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if (auth()->user()?->isAdmin()) {
            return redirect()->intended(route('filament.admin.pages.dashboard'));
        }

        return redirect()->intended(config('fortify.home'));
    }
}
