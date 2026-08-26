<?php

namespace App\Http\Controllers;

use App\Actions\Cart\CartManager;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartManager $cart) {}

    /**
     * Страница корзины.
     */
    public function index(): View
    {
        $footerCategories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        return view('cart.index', [
            'lines' => $this->cart->lines(),
            'total' => $this->cart->total(),
            'footerCategories' => $footerCategories,
        ]);
    }

    /**
     * Добавить товар в корзину (AJAX с карточек и страницы товара).
     */
    public function add(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $data = $request->validate([
                'product_id' => ['required', 'integer', 'exists:products,id'],
                'quantity' => ['nullable', 'integer', 'min:1', 'max:'.Cart::MAX_QUANTITY],
            ], $this->messages());
        } catch (ValidationException $e) {
            return $this->validationError($request, $e);
        }

        $product = Product::findOrFail($data['product_id']);

        if (! $this->cart->isAvailable($product)) {
            return $this->respond($request, false, 'Товар недоступен для заказа.', 422);
        }

        $this->cart->add($product, $data['quantity'] ?? 1);

        return $this->respond(
            $request,
            true,
            'Товар добавлен в корзину.',
            lineTotal: $product->price * ($data['quantity'] ?? 1),
            quantity: $this->cart->quantity($product->id),
        );
    }

    /**
     * Изменить количество позиции.
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $data = $request->validate([
                'product_id' => ['required', 'integer', 'exists:products,id'],
                'quantity' => ['required', 'integer', 'min:1', 'max:'.Cart::MAX_QUANTITY],
            ], $this->messages());
        } catch (ValidationException $e) {
            return $this->validationError($request, $e);
        }

        $product = Product::findOrFail($data['product_id']);

        if (! $this->cart->contains($product)) {
            return $this->respond($request, false, 'Товара нет в корзине.', 404);
        }

        $this->cart->updateQuantity($product, $data['quantity']);

        return $this->respond(
            $request,
            true,
            'Количество обновлено.',
            lineTotal: $product->price * $data['quantity'],
            quantity: $this->cart->quantity($product->id),
        );
    }

    /**
     * Удалить позицию из корзины.
     */
    public function remove(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $data = $request->validate([
                'product_id' => ['required', 'integer', 'exists:products,id'],
            ], $this->messages());
        } catch (ValidationException $e) {
            return $this->validationError($request, $e);
        }

        if (! $this->cart->contains(Product::findOrFail($data['product_id']))) {
            return $this->respond($request, false, 'Товара нет в корзине.', 404);
        }

        $this->cart->remove($data['product_id']);

        return $this->respond(
            $request,
            true,
            'Товар удалён из корзины.',
            quantity: $this->cart->quantity($data['product_id']),
        );
    }

    /**
     * Полностью очистить корзину.
     */
    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        $this->cart->clear();

        return $this->respond($request, true, 'Корзина очищена.');
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'product_id.required' => 'Не указан товар.',
            'product_id.integer' => 'Некорректный идентификатор товара.',
            'product_id.exists' => 'Товар не найден.',
            'quantity.required' => 'Не указано количество.',
            'quantity.integer' => 'Некорректное количество.',
            'quantity.min' => 'Количество не может быть меньше 1.',
            'quantity.max' => 'Количество не может превышать '.Cart::MAX_QUANTITY.'.',
        ];
    }

    /**
     * Ошибка валидации: для AJAX возвращаем JSON с русским сообщением
     * (в проекте исключения на web-роутах не рендерятся как JSON, см.
     * shouldRenderJsonWhen в bootstrap/app.php), для обычных запросов —
     * стандартный редирект Laravel.
     */
    private function validationError(Request $request, ValidationException $e): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            throw $e;
        }

        $firstMessage = Collection::make($e->errors())->flatten()->first() ?? 'Некорректные данные.';

        return response()->json([
            'success' => false,
            'message' => $firstMessage,
            'errors' => $e->errors(),
            'count' => $this->cart->count(),
            'total' => $this->cart->total(),
        ], 422);
    }

    /**
     * JSON-ответ для AJAX (карточки, шапка, страница корзины) либо редирект
     * с flash-сообщением для обычных POST-запросов.
     */
    private function respond(
        Request $request,
        bool $success,
        string $message,
        int $status = 200,
        ?int $lineTotal = null,
        ?int $quantity = null,
    ): JsonResponse|RedirectResponse {
        $payload = [
            'success' => $success,
            'message' => $message,
            'count' => $this->cart->count(),
            'total' => $this->cart->total(),
        ];

        if ($lineTotal !== null) {
            $payload['line_total'] = $lineTotal;
        }

        // Количество единиц конкретного товара в корзине — для карточек товаров
        // и страницы товара (кнопка «Купить» → количество в корзине).
        if ($quantity !== null) {
            $payload['quantity'] = $quantity;
        }

        if ($request->expectsJson()) {
            return response()->json($payload, $status);
        }

        return $status >= 400
            ? back()->withErrors(['cart' => $message])->withInput()
            : back()->with('status', $message);
    }
}
