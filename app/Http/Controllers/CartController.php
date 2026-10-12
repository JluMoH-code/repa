<?php

namespace App\Http\Controllers;

use App\Actions\Cart\CartManager;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
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
                'variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $request->input('product_id'))],
                'quantity' => ['nullable', 'integer', 'min:1', 'max:'.Cart::MAX_QUANTITY],
            ], $this->messages());
        } catch (ValidationException $e) {
            return $this->validationError($request, $e);
        }

        $product = Product::findOrFail($data['product_id']);
        $variant = $this->cart->resolveVariant($product, isset($data['variant_id']) ? ProductVariant::findOrFail($data['variant_id']) : null);

        if (! $this->cart->isAvailable($product, $variant)) {
            return $this->respond($request, false, 'Товар недоступен для заказа.', 422);
        }

        try {
            $this->cart->add($product, $data['quantity'] ?? 1, $variant);
        } catch (ValidationException $e) {
            return $this->validationError($request, $e);
        }

        return $this->respond(
            $request,
            true,
            'Товар добавлен в корзину.',
            lineTotal: ($variant?->price ?? $product->price) * ($data['quantity'] ?? 1),
            quantity: $this->cart->quantity($product->id, $variant?->id),
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
                'variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $request->input('product_id'))],
                'quantity' => ['required', 'integer', 'min:1', 'max:'.Cart::MAX_QUANTITY],
            ], $this->messages());
        } catch (ValidationException $e) {
            return $this->validationError($request, $e);
        }

        $product = Product::findOrFail($data['product_id']);
        $this->cart->lines();
        $variant = $this->cart->resolveVariant($product, isset($data['variant_id']) ? ProductVariant::findOrFail($data['variant_id']) : null);

        if (! $this->cart->contains($product, $variant?->id)) {
            return $this->respond($request, false, 'Товара нет в корзине.', 404);
        }

        try {
            $this->cart->updateQuantity($product, $data['quantity'], $variant);
        } catch (ValidationException $e) {
            return $this->validationError($request, $e);
        }

        return $this->respond(
            $request,
            true,
            'Количество обновлено.',
            lineTotal: ($variant?->price ?? $product->price) * $data['quantity'],
            quantity: $this->cart->quantity($product->id, $variant?->id),
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
                'variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $request->input('product_id'))],
            ], $this->messages());
        } catch (ValidationException $e) {
            return $this->validationError($request, $e);
        }

        $this->cart->lines();
        $product = Product::findOrFail($data['product_id']);
        $variant = $this->cart->resolveVariant($product, isset($data['variant_id']) ? ProductVariant::findOrFail($data['variant_id']) : null);
        if (! $this->cart->contains($product, $variant?->id)) {
            return $this->respond($request, false, 'Товара нет в корзине.', 404);
        }

        $this->cart->remove($data['product_id'], $variant?->id);

        return $this->respond(
            $request,
            true,
            'Товар удалён из корзины.',
            quantity: $this->cart->quantity($data['product_id'], $variant?->id),
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
     * Актуальное состояние корзины (количества по product_id + итоги).
     * Используется фронтендом при возврате «назад» (bfcache-восстановление
     * страницы со старым DOM) для синхронизации карточек товаров и шапки.
     */
    public function quantities(): JsonResponse
    {
        $this->cart->lines();

        return response()->json([
            'quantities' => $this->cart->quantities(),
            'count' => $this->cart->count(),
            'total' => $this->cart->total(),
        ]);
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
