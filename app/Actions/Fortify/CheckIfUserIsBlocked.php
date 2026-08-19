<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

/**
 * Шаг пайплайна входа: если пользователь заблокирован — не пускаем
 * с сообщением об ошибке.
 */
class CheckIfUserIsBlocked
{
    public function handle(Request $request, $next)
    {
        $user = User::query()
            ->where(Fortify::username(), $request->{Fortify::username()})
            ->first();

        if ($user?->is_blocked) {
            throw ValidationException::withMessages([
                Fortify::username() => ['Аккаунт заблокирован. Обратитесь в поддержку магазина.'],
            ]);
        }

        return $next($request);
    }
}
