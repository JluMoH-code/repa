<?php

namespace App\Http\Controllers;

use App\Actions\Cart\CartManager;
use App\Actions\Orders\EmptyCartException;
use App\Actions\Orders\OrderManager;
use App\Actions\Orders\ProductUnavailableException;
use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartManager $cart,
        private readonly OrderManager $orders,
    ) {}

    /**
     * Страница оформления заказа: форма с контактами, адресом доставки и списком товаров.
     */
    public function create(): View|RedirectResponse
    {
        $lines = $this->cart->lines();

        if ($lines->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('status', 'Корзина пуста — добавьте товары перед оформлением.');
        }

        $user = auth()->user();

        return view('checkout.index', [
            'lines' => $lines,
            'total' => $this->cart->total(),
            'footerCategories' => $this->footerCategories(),
            'cities' => $this->cities(),
            'defaults' => [
                'customer_name' => old('customer_name', $user?->name),
                'customer_email' => old('customer_email', $user?->email),
                'customer_phone' => old('customer_phone', $user?->phone),
            ],
        ]);
    }

    /**
     * Оформить заказ: валидация → OrderManager::createFromCart → редирект.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:180'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'delivery_city' => ['required', 'string', 'max:120'],
            'delivery_postcode' => ['nullable', 'string', 'max:10'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], $this->messages());

        try {
            $order = $this->orders->createFromCart(
                customer: [
                    'customer_name' => $data['customer_name'],
                    'customer_email' => $data['customer_email'],
                    'customer_phone' => $data['customer_phone'],
                ],
                delivery: [
                    'delivery_city' => $data['delivery_city'],
                    'delivery_postcode' => $data['delivery_postcode'] ?? null,
                    'delivery_address' => $data['delivery_address'],
                ],
                comment: $data['comment'] ?? null,
            );
        } catch (EmptyCartException) {
            return redirect()
                ->route('cart.index')
                ->with('status', 'Корзина пуста — добавьте товары перед оформлением.');
        } catch (ProductUnavailableException $e) {
            return back()
                ->withInput()
                ->withErrors(['cart' => $e->getMessage()]);
        }

        // Авторизованный — на страницу заказа в кабинете; гость — на публичную success-страницу.
        if (auth()->check()) {
            return redirect()
                ->route('cabinet.orders.show', $order)
                ->with('status', 'Заказ '.$order->number.' оформлен. Мы свяжемся с вами для подтверждения.');
        }

        return redirect()
            ->route('checkout.success', ['order' => $order->number, 'email' => $order->customer_email]);
    }

    /**
     * Страница «Спасибо за заказ» для гостя: показывает номер и краткое описание,
     * доступ к ней — по связке (number + email), чтобы посторонний не подсмотрел
     * чужой заказ, угадав только номер.
     */
    public function success(Request $request, string $order): View|RedirectResponse
    {
        $email = (string) $request->query('email', '');

        $orderModel = Order::query()
            ->with('items')
            ->where('number', $order)
            ->where('customer_email', $email)
            ->first();

        if ($orderModel === null) {
            return redirect()->route('storefront');
        }

        return view('checkout.success', [
            'order' => $orderModel,
            'footerCategories' => $this->footerCategories(),
            'isGuest' => auth()->guest(),
            // Email занят — значит у гостя уже есть аккаунт: вместо создания
            // показываем форму входа (после входа заказ виден в кабинете).
            'emailTaken' => User::query()->where('email', $orderModel->customer_email)->exists(),
        ]);
    }

    /**
     * Создать аккаунт из гостевого заказа (страница успеха): гость задаёт
     * пароль → аккаунт создаётся из данных заказа → автовход → кабинет.
     */
    public function createAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], $this->accountMessages());

        $order = Order::query()
            ->where('number', $data['order_number'])
            ->whereNull('user_id')
            ->first();

        abort_unless($order, 404);

        if (User::query()->where('email', $order->customer_email)->exists()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Аккаунт с таким email уже существует — войдите под своим паролем.']);
        }

        try {
            $user = $this->orders->createGuestAccount($order, $data['password']);
        } catch (Throwable) {
            return back()
                ->withErrors(['email' => 'Не удалось создать аккаунт: email уже зарегистрирован. Войдите под своим паролем.']);
        }

        auth()->login($user);

        return redirect()
            ->route('cabinet.orders.show', $order)
            ->with('status', 'Аккаунт создан. Заказ привязан к вашему профилю.');
    }

    /**
     * @return Collection<int, City>
     */
    private function cities(): Collection
    {
        return City::query()->orderBy('name')->get();
    }

    /**
     * @return array<string, string>
     */
    private function accountMessages(): array
    {
        return [
            'order_number.required' => 'Не указан номер заказа.',
            'order_number.max' => 'Некорректный номер заказа.',
            'password.required' => 'Придумайте пароль.',
            'password.min' => 'Пароль должен быть не короче 8 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
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
}
