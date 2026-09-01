<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'delivery_city',
        'delivery_postcode',
        'delivery_address',
        'comment',
        'status',
        'subtotal',
        'total',
        'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'placed_at' => 'datetime',
            'subtotal' => 'integer',
            'total' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Генерация человекочитаемого номера «Р-YYYY-NNNNNN» на создании,
        // если не задан явно. Уникальность — retry в цикле (5 попыток).
        static::creating(function (self $order) {
            if (empty($order->number)) {
                $order->number = self::generateNumber();
            }
        });

        // Guard: статус-финал нельзя сменить на другой, плюс «откатить в New»
        // запрещено всегда (защита от случайных кликов в админке).
        static::saving(function (self $order) {
            if (! $order->exists) {
                return;
            }

            $original = $order->getOriginal('status');

            if ($original === null) {
                return;
            }

            /** @var OrderStatus|null $current */
            $current = $order->status;
            /** @var OrderStatus|null $previous */
            $previous = $original instanceof OrderStatus
                ? $original
                : OrderStatus::tryFrom((string) $original);

            if ($current === null || $previous === null || $current === $previous) {
                return;
            }

            if ($previous->isFinal()) {
                throw new InvalidArgumentException(
                    'Нельзя изменить статус завершённого заказа ('.$previous->getLabel().').'
                );
            }

            if ($current === OrderStatus::New) {
                throw new InvalidArgumentException(
                    'Нельзя вернуть заказ в статус «Новый».'
                );
            }
        });
    }

    /**
     * Сгенерировать уникальный номер заказа. Сначала пробуем по порядку
     * (порядковый номер за текущий год), при коллизии — случайный хвост.
     */
    private static function generateNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "Р-{$year}-";

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = $attempt === 0
                ? $prefix.str_pad((string) (self::nextYearlySequence($year) + 1), 6, '0', STR_PAD_LEFT)
                : $prefix.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

            if (! self::query()->where('number', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Fallback: случайный хвост из букв/цифр, чтобы гарантированно получить уникальный номер.
        return $prefix.strtoupper(Str::random(6));
    }

    /**
     * Максимальный порядковый номер за текущий год (по уже созданным заказам).
     * Без DB-специфичного SQL — работает и на PostgreSQL, и на SQLite в тестах.
     */
    private static function nextYearlySequence(string $year): int
    {
        $prefix = "Р-{$year}-";
        $max = 0;

        foreach (self::query()->where('number', 'like', $prefix.'%')->pluck('number') as $number) {
            $seq = (int) substr((string) $number, strlen($prefix));

            if ($seq > $max) {
                $max = $seq;
            }
        }

        return $max;
    }

    /**
     * Заказы конкретного покупателя: для авторизованного — по user_id (с
     * запасом по email для старых гостевых заказов), для гостя — только по email.
     */
    public function scopeForCustomer(Builder $query, ?int $userId, ?string $email): Builder
    {
        return $query->where(function (Builder $q) use ($userId, $email) {
            if ($userId !== null) {
                $q->where('user_id', $userId);
            }

            if ($email !== null && $email !== '') {
                $q->orWhere('customer_email', $email);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
