<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CabinetTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['cabinet.index', 'cabinet.profile', 'cabinet.orders'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_cabinet_index_shows_greeting(): void
    {
        $user = $this->user(['name' => 'Иван Петров']);

        $this->actingAs($user)->get(route('cabinet.index'))
            ->assertOk()
            ->assertSee('Иван Петров')
            ->assertSee('Личный кабинет')
            ->assertSee('Избранное');
    }

    public function test_user_can_update_profile(): void
    {
        $user = $this->user();

        $this->actingAs($user)->from(route('cabinet.profile'))->post(route('cabinet.profile.update'), [
            'name' => 'Новое имя',
            'email' => $user->email,
            'phone' => '+7 (999) 123-45-67',
            'birth_date' => '1990-01-15',
            'gender' => 'male',
        ])->assertRedirect(route('cabinet.profile'));

        $user->refresh();

        $this->assertSame('Новое имя', $user->name);
        $this->assertSame('+79991234567', $user->phone);
        $this->assertSame('1990-01-15', $user->birth_date?->format('Y-m-d'));
        $this->assertSame('male', $user->gender?->value);
    }

    public function test_profile_validation_rejects_invalid_gender_and_email(): void
    {
        $user = $this->user();
        $other = $this->user(['email' => 'taken@example.com']);

        $this->actingAs($user)->from(route('cabinet.profile'))->post(route('cabinet.profile.update'), [
            'name' => 'Имя',
            'email' => 'taken@example.com',
            'gender' => 'unknown',
        ])->assertRedirect(route('cabinet.profile'))
            ->assertSessionHasErrors(['email', 'gender'], null, 'updateProfileInformation');
    }

    public function test_user_can_change_password(): void
    {
        $user = $this->user();

        $this->actingAs($user)->from(route('cabinet.profile'))->post(route('cabinet.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('cabinet.profile'));

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = $this->user();

        $this->actingAs($user)->from(route('cabinet.profile'))->post(route('cabinet.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('cabinet.profile'))
            ->assertSessionHasErrors(['current_password'], null, 'updatePassword');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_orders_page_shows_placeholder(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get(route('cabinet.orders'))
            ->assertOk()
            ->assertSee('Заказов пока нет');
    }
}
