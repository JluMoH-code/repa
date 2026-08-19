<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Доступ к админке только для администраторов: покупателя молча
 * перенаправляем в личный кабинет.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isAdmin() || $user->is_blocked) {
            return redirect()->route('cabinet.index');
        }

        return $next($request);
    }
}
