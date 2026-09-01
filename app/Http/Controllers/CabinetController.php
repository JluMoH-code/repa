<?php

namespace App\Http\Controllers;

use App\Actions\Cart\CartManager;
use App\Actions\Favorites\FavoriteManager;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Actions\Orders\OrderManager;
use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use RuntimeException;

class CabinetController extends Controller
{
    public function __construct(
        private readonly CartManager $cart,
        private readonly FavoriteManager $favorites,
        private readonly OrderManager $orders,
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

    /**
     * Форма редактирования заказа (контакты/доставка/комментарий; до отправки).
     */
    public function orderEdit(Order $order): View|RedirectResponse
    {
        $this->assertOrderBelongs($order);

        if (! $this->orders->isEditableByCustomer($order)) {
            return redirect()
                ->route('cabinet.orders.show', $order)
                ->with('status', 'Заказ уже отправлен или завершён — редактирование недоступно.');
        }

        return view('cabinet.order-edit', [
            'order' => $order,
            'cities' => City::query()->orderBy('name')->get(),
            'footerCategories' => $this->footerCategories(),
        ]);
    }

    /**
     * Сохранить изменения заказа.
     */
    public function orderUpdate(Request $request, Order $order): RedirectResponse
    {
        $this->assertOrderBelongs($order);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:180'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'delivery_city' => ['required', 'string', 'max:120'],
            'delivery_postcode' => ['nullable', 'string', 'max:10'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], $this->orderMessages());

        try {
            $this->orders->updateCustomerData($order, $data);
        } catch (RuntimeException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()
            ->route('cabinet.orders.show', $order)
            ->with('status', 'Данные заказа обновлены.');
    }

    /**
     * Отменить заказ покупателем (до отправки).
     */
    public function orderCancel(Order $order): RedirectResponse
    {
        $this->assertOrderBelongs($order);

        try {
            $this->orders->cancelByCustomer($order);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('cabinet.orders.show', $order)
                ->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()
            ->route('cabinet.orders.show', $order)
            ->with('status', 'Заказ отменён.');
    }

    private function assertOrderBelongs(Order $order): void
    {
        $user = auth()->user();
        $belongs = ($order->user_id !== null && $order->user_id === $user->id)
            || strcasecmp((string) $order->customer_email, (string) $user->email) === 0;

        abort_unless($belongs, 404);
    }

    /**
     * @return array<string, string>
     */
    private function orderMessages(): array
    {
        return [
            'customer_name.required' => 'Укажите имя.',
            'customer_name.max' => 'Имя не должно быть длиннее 120 символов.',
            'customer_email.required' => 'Укажите email.',
            'customer_email.email' => 'Некорректный формат email.',
            'customer_email.max' => 'Email не должен быть длиннее 180 символов.',
            'customer_phone.required' => 'Укажите телефон.',
            'customer_phone.max' => 'Телефон не должен быть длиннее 30 символов.',
            'delivery_city.required' => 'Укажите город.',
            'delivery_city.max' => 'Название города не должно быть длиннее 120 символов.',
            'delivery_postcode.max' => 'Индекс не должен быть длиннее 10 символов.',
            'delivery_address.required' => 'Укажите адрес доставки.',
            'delivery_address.max' => 'Адрес не должен быть длиннее 255 символов.',
            'comment.max' => 'Комментарий не должен быть длиннее 1000 символов.',
        ];
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
