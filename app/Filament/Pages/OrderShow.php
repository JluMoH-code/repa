<?php

namespace App\Filament\Pages;

use App\Actions\Orders\OrderManager;
use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Детальная страница заказа в админке: контакты, адрес, состав, смена статуса.
 * Маршрут /admin/orders/{order} (slug — номер заказа), скрыта из навигации.
 */
class OrderShow extends Page
{
    protected string $view = 'filament.pages.order-show';

    protected static string|\UnitEnum|null $navigationGroup = 'Магазин';

    public $order;

    public string $newStatus = '';

    public array $itemQuantities = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() && ! auth()->user()->is_blocked;
    }

    /**
     * Страница открывается только по ссылке из списка заказов.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /**
     * Путь — вложенный в список заказов: /admin/orders/{order}.
     * (В Filament 5 `$routePath` используется только Dashboard'ом,
     * для остальных страниц путь строится из slug — переопределяем явно.)
     */
    public static function getRoutePath(Panel $panel): string
    {
        return '/orders/{order}';
    }

    public function mount(string $order): void
    {
        abort_unless(static::canAccess(), 403);
        $orderModel = Order::query()->with('items')->where('number', $order)->first();

        if ($orderModel === null) {
            throw new ModelNotFoundException('Заказ не найден.');
        }

        $this->order = $orderModel;
        $this->newStatus = $orderModel->status->value;
        $this->itemQuantities = $orderModel->items->pluck('quantity', 'id')->all();
    }

    public function saveStatus(): void
    {
        abort_unless(static::canAccess(), 403);
        $newStatus = OrderStatus::tryFrom($this->newStatus);

        if ($newStatus === null) {
            Notification::make()->title('Некорректный статус.')->danger()->send();

            return;
        }

        try {
            app(OrderManager::class)->changeStatus($this->order, $newStatus);
            $this->order->refresh();

            Notification::make()
                ->title("Статус заказа {$this->order->number} изменён на «{$newStatus->getLabel()}»")
                ->success()
                ->send();
        } catch (InvalidArgumentException $e) {
            // Возвращаем select в актуальное значение и показываем причину.
            $this->newStatus = $this->order->status->value;
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function saveQuantities(): void
    {
        abort_unless(static::canAccess(), 403);
        app(OrderManager::class)->updateItemQuantities($this->order, $this->itemQuantities);
        $this->order->refresh();
        Notification::make()->title('Состав заказа обновлён')->success()->send();
    }

    public function getStatuses(): array
    {
        return app(Orders::class)->availableStatuses($this->order);
    }
}
