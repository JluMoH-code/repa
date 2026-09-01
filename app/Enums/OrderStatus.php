<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Processing = 'processing';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Новый',
            self::Processing => 'В обработке',
            self::Paid => 'Оплачен',
            self::Shipped => 'Отправлен',
            self::Delivered => 'Доставлен',
            self::Cancelled => 'Отменён',
            self::Refunded => 'Возврат',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Processing => 'warning',
            self::Paid => 'info',
            self::Shipped => 'info',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
            self::Refunded => 'danger',
        };
    }

    /**
     * Финальные статусы — из них нельзя перейти в любой другой.
     * Контролируется guard'ом в App\Models\Order::saving().
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::Delivered,
            self::Cancelled,
            self::Refunded,
        ], true);
    }
}
