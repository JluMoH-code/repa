<?php

namespace App\Http\Controllers;

use App\Actions\Favorites\FavoriteManager;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FavoritesController extends Controller
{
    public function __construct(private readonly FavoriteManager $favorites) {}

    /**
     * Страница избранного в личном кабинете.
     */
    public function index(): View
    {
        $footerCategories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        return view('cabinet.favorites', [
            'lines' => $this->favorites->lines(),
            'footerCategories' => $footerCategories,
        ]);
    }

    /**
     * Переключить товар в избранном (AJAX с карточек и страницы избранного).
     */
    public function toggle(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $data = $request->validate([
                'product_id' => ['required', 'integer', 'exists:products,id'],
            ], $this->messages());
        } catch (ValidationException $e) {
            return $this->validationError($request, $e);
        }

        $favorite = $this->favorites->toggle($data['product_id']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'favorite' => $favorite,
                'count' => $this->favorites->count(),
            ]);
        }

        return back()->with('status', $favorite ? 'Товар добавлен в избранное.' : 'Товар удалён из избранного.');
    }

    /**
     * Удалить товар из избранного.
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

        $this->favorites->remove($data['product_id']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'count' => $this->favorites->count(),
            ]);
        }

        return back()->with('status', 'Товар удалён из избранного.');
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
        ];
    }

    /**
     * Ошибка валидации: для AJAX — JSON с русским сообщением, для обычных
     * запросов — стандартный редирект Laravel.
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
            'count' => $this->favorites->count(),
        ], 422);
    }
}
