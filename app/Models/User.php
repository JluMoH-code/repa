<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'birth_date', 'gender', 'role', 'is_blocked', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date:Y-m-d',
            'gender' => Gender::class,
            'role' => UserRole::class,
            'is_blocked' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Доступ в панель открыт всем вошедшим — проверку роли делает
     * middleware EnsureUserIsAdmin (покупатель уходит в кабинет).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Заказы пользователя (созданные в авторизованном режиме).
     * Гостевые заказы не привязаны к user_id и не попадают в эту выборку —
     * они показываются покупателю по email через Order::scopeForCustomer().
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
