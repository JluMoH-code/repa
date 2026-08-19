<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAdminLocale
{
    /**
     * Админка всегда на русском: у Filament нет собственной локали — он берёт
     * глобальную локаль Laravel (в .env стоит APP_LOCALE=en, менять .env нельзя).
     * Поэтому для запросов админки принудительно ставим 'ru': все кнопки
     * (Создать / Изменить / Удалить / Сохранить / Отмена и т.д.) берутся из
     * встроенных русских переводов Filament (папка lang/ru в его пакетах).
     * Витрину и кабинет middleware не затрагивает.
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('ru');

        return $next($request);
    }
}
