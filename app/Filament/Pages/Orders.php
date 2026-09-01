<?php

namespace App\Filament\Pages;

use App\Actions\Orders\OrderManager;
use App\Enums\OrderStatus;
use App\Models\Order;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Livewire\WithPagination;

/**
 * Список заказов в админке: поиск по номеру/email/имени, фильтр по статусу,
 * inline-смена статуса прямо из таблицы.
 *
 * Написан как обычная Livewire-страница (по образцу ImportProducts), а не
 * через Schema/Form API — см. AGENTS.md.
 */
class Orders extends Page
{
    use WithPagination;

    protected string $view = 'filament.pages.orders';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Заказы';

    protected static string|\UnitEnum|null $navigationGroup = 'Магазин';

    protected static ?int $navigationSort = 1;

    public string $search = '';

    public ?string $statusFilter = null;

    public function mount(): void
    {
        $this->statusFilter = $this->statusFilter ?? OrderStatus::New->value;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Inline-смена статуса из строки таблицы.
     */
    public function changeStatus(int $orderId, string $status): void
    {
        $order = Order::findOrFail($orderId);
        $newStatus = OrderStatus::tryFrom($status);

        if ($newStatus === null) {
            Notification::make()->title('Некорректный статус.')->danger()->send();

            return;
        }

        try {
            app(OrderManager::class)->changeStatus($order, $newStatus);
            Notification::make()
                ->title("Заказ {$order->number}: статус изменён на «{$newStatus->getLabel()}»")
                ->success()
                ->send();
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function orders(): LengthAwarePaginator
    {
        return Order::query()
            ->with('items')
            ->withCount('items')
            ->when($this->statusFilter !== '' && $this->statusFilter !== null, function (Builder $query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->search !== '', function (Builder $query) {
                $like = '%'.mb_strtolower($this->search).'%';
                $query->where(function (Builder $q) use ($like) {
                    $q->whereRaw('LOWER(number) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(customer_email) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(customer_name) LIKE ?', [$like]);
                });
            })
            ->latest('placed_at')
            ->paginate(15);
    }

    public function getStatuses(): array
    {
        return OrderStatus::cases();
    }
}
