<?php

namespace App\Http\Controllers;

use App\Actions\Cart\CartManager;
use App\Actions\Favorites\FavoriteManager;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\Category;
use App\Models\Order;
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
            'ordersCount' => $this->ordersCount(),
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
     * Список заказов покупателя (свои + гостевые, оформленные на тот же email).
     */
    public function orders(): View
    {
        $user = auth()->user();
        $orders = Order::query()
            ->forCustomer($user->id, $user->email)
            ->withCount('items')
            ->latest('placed_at')
            ->paginate(10);

        return view('cabinet.orders', [
            'orders' => $orders,
            'footerCategories' => $this->footerCategories(),
        ]);
    }

    /**
     * Детальная страница заказа в кабинете.
     * Чужие заказы (другого user_id или email) — 404.
     */
    public function orderShow(Order $order): View
    {
        $user = auth()->user();
        $belongs = ($order->user_id !== null && $order->user_id === $user->id)
            || strcasecmp((string) $order->customer_email, (string) $user->email) === 0;

        abort_unless($belongs, 404);

        $order->load('items');

        return view('cabinet.order-show', [
            'order' => $order,
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

    /**
     * Сколько заказов у пользователя (для карточки «Заказы» в обзоре кабинета).
     */
    private function ordersCount(): int
    {
        $user = auth()->user();

        return Order::query()
            ->forCustomer($user->id, $user->email)
            ->count();
    }
}
