<?php

namespace App\Actions\Favorites;

use App\Models\Favorite;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * Избранное по образцу корзины:
 * - гость — данные в сессии (ключ `favorites`: [product_id => true]);
 * - авторизованный — строки в таблице `favorites`;
 * - при входе гостевое избранное сливается с избранным пользователя
 *   (см. App\Listeners\MergeGuestDataOnLogin).
 *
 * Регистрируется как singleton в AppServiceProvider: id товаров кэшируются
 * на время запроса, чтобы на страницах с карточками не было N+1 запросов.
 */
class FavoriteManager
{
    private const SESSION_KEY = 'favorites';

    /** @var array<int, int>|null */
    private ?array $cachedIds = null;

    public function __construct(private readonly AuthFactory $auth) {}

    /**
     * Позиции избранного: товары с изображениями.
     *
     * @return Collection<int, Product>
     */
    public function lines(): Collection
    {
        $ids = $this->ids();

        if ($ids === []) {
            return collect();
        }

        return Product::query()
            ->with('images')
            ->whereKey($ids)
            ->get();
    }

    /**
     * Добавить товар в избранное.
     */
    public function add(int $productId): void
    {
        if ($this->isGuest()) {
            $ids = $this->sessionIds();
            $ids[$productId] = true;
            $this->saveSessionIds($ids);
            $this->forgetCache();

            return;
        }

        Favorite::query()->firstOrCreate([
            'user_id' => $this->auth->id(),
            'product_id' => $productId,
        ]);

        $this->forgetCache();
    }

    /**
     * Убрать товар из избранного.
     */
    public function remove(int $productId): void
    {
        if ($this->isGuest()) {
            $ids = $this->sessionIds();
            unset($ids[$productId]);
            $this->saveSessionIds($ids);
            $this->forgetCache();

            return;
        }

        Favorite::query()
            ->where('user_id', $this->auth->id())
            ->where('product_id', $productId)
            ->delete();

        $this->forgetCache();
    }

    /**
     * Переключить товар в избранном. Возвращает новое состояние (true — добавлен).
     */
    public function toggle(int $productId): bool
    {
        if ($this->has($productId)) {
            $this->remove($productId);

            return false;
        }

        $this->add($productId);

        return true;
    }

    /**
     * Есть ли товар в избранном.
     */
    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    /**
     * Количество товаров в избранном (счётчик в шапке/сайдбаре).
     */
    public function count(): int
    {
        return count($this->ids());
    }

    /**
     * ID товаров в избранном (кэшируются на время запроса).
     *
     * @return array<int, int>
     */
    public function ids(): array
    {
        if ($this->cachedIds !== null) {
            return $this->cachedIds;
        }

        $ids = $this->isGuest()
            ? array_keys($this->sessionIds())
            : Favorite::query()
                ->where('user_id', $this->auth->id())
                ->pluck('product_id')
                ->all();

        $this->cachedIds = $ids;

        return $ids;
    }

    /**
     * Слить гостевое (сессионное) избранное с избранным пользователя и очистить сессию.
     * Вызывается при успешном входе (событие Illuminate\Auth\Events\Login).
     */
    public function mergeGuestFavorites(User $user): void
    {
        $sessionIds = $this->sessionIds();

        if ($sessionIds === []) {
            return;
        }

        $existing = Favorite::query()
            ->where('user_id', $user->id)
            ->pluck('product_id')
            ->all();

        $toAdd = array_diff(array_keys($sessionIds), $existing);

        if ($toAdd !== []) {
            Favorite::query()->insert(
                collect($toAdd)->map(fn (int $productId) => [
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all()
            );
        }

        Session::forget(self::SESSION_KEY);
        $this->forgetCache();
    }

    private function isGuest(): bool
    {
        return ! $this->auth->guard()->check();
    }

    private function forgetCache(): void
    {
        $this->cachedIds = null;
    }

    /**
     * @return array<int, bool>
     */
    private function sessionIds(): array
    {
        $ids = Session::get(self::SESSION_KEY, []);

        return is_array($ids) ? $ids : [];
    }

    /**
     * @param  array<int, bool>  $ids
     */
    private function saveSessionIds(array $ids): void
    {
        Session::put(self::SESSION_KEY, $ids);
    }
}
