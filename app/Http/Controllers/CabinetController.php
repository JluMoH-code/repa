<?php

namespace App\Http\Controllers;

use App\Actions\Cart\CartManager;
use App\Actions\Favorites\FavoriteManager;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CabinetController extends Controller
{
    public function __construct(
        private readonly CartManager $cart,
        private readonly FavoriteManager $favorites,
    ) {}

    /**
     * Обзор личного кабинета.
     */
    public function index(): View
    {
        return view('cabinet.index', [
            'favoritesCount' => $this->favorites->count(),
            'cartCount' => $this->cart->count(),
            'footerCategories' => $this->footerCategories(),
        ]);
    }

    /**
     * Страница профиля (данные + смена пароля).
     */
    public function profile(): View
    {
        return view('cabinet.profile', [
            'footerCategories' => $this->footerCategories(),
        ]);
    }

    /**
     * Обновить данные профиля.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        app(UpdateUserProfileInformation::class)->update($request->user(), $request->all());

        return back()->with('status', 'Данные профиля обновлены.');
    }

    /**
     * Сменить пароль.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        app(UpdateUserPassword::class)->update($request->user(), $request->all());

        return back()->with('status', 'Пароль изменён.');
    }

    /**
     * Мои заказы — заглушка до этапа оформления заказов.
     */
    public function orders(): View
    {
        return view('cabinet.orders', [
            'footerCategories' => $this->footerCategories(),
        ]);
    }

    private function footerCategories(): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();
    }
}
