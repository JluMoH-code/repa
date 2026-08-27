<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\WelcomeBanner;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SetAdminLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            // Единый вход через Fortify (/login), отдельной страницы входа у панели нет.
            ->authGuard('web')
            // Палитра витрины Repa: primary — зелёный бренд (Tailwind green,
            // #16a34a = 600), gray — slate (нейтрали витрины), success — emerald
            // (бейджи «В наличии»), warning — оранжевый акцент, info — blue,
            // danger — red. Оттенки уходят в runtime CSS-переменные панели
            // (--primary-*, --gray-* и т.д.), из которых собрана тема.
            ->colors([
                'primary' => Color::Green,
                'gray' => Color::Slate,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'info' => Color::Blue,
                'danger' => Color::Red,
            ])
            // Кастомная тема под вид витрины (см. resources/css/filament/admin/theme.css).
            // Шрифт — локальный Inter Variable из комплекта Filament (без `->font()`,
            // т.к. тот грузит шрифт с Bunny CDN, а витрина — системный стек без CDN).
            ->viteTheme('resources/css/filament/admin/theme.css')
            // Тёмная тема отключена: витрина Repa светлая, и админка должна быть
            // такой же (в Filament по умолчанию режим «система/из localStorage»,
            // из-за чего админка могла рендериться тёмной). Переключатель темы
            // в топбаре при этом не показывается, localStorage принудительно = light.
            ->darkMode(false)
            ->brandName('Repa')
            ->brandLogo(asset('images/repa-logo.svg'))
            ->brandLogoHeight('1.5rem')
            ->favicon(asset('images/repa-logo.svg'))
            // Клик по логотипу/названию в сайдбаре ведёт на витрину.
            ->homeUrl(fn () => route('storefront'))
            // Кнопка «Открыть магазин» в шапке админки — как на дашборде.
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): HtmlString => new HtmlString(view('filament.partials.open-storefront-link')->render()),
            )
            // Скрипты панели: страховка от «зависшей» кнопки «Загрузка файла...»
            // при загрузке изображений (см. resources/js/filament/admin/panel.js).
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): HtmlString => new HtmlString(view('filament.partials.admin-panel-scripts')->render()),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                WelcomeBanner::class,
                StatsOverview::class,
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                // Админка всегда на русском (кнопки и уведомления Filament).
                SetAdminLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsAdmin::class,
            ]);
    }
}
