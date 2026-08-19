<?php

namespace Tests\Feature;

use App\Actions\Settings\SettingsManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_redirected_to_admin_panel_after_login(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('filament.admin.pages.dashboard'));
    }

    public function test_customer_is_redirected_to_cabinet_after_login(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(config('fortify.home'));
    }

    public function test_customer_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')
            ->assertRedirect(route('cabinet.index'));
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')
            ->assertSuccessful();
    }

    public function test_guest_is_redirected_to_login_on_admin(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('login'));
    }

    public function test_blocked_user_cannot_login(): void
    {
        $user = User::factory()->blocked()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_blocked_admin_cannot_access_admin_panel(): void
    {
        $admin = User::factory()->admin()->blocked()->create();

        $this->actingAs($admin)->get('/admin')
            ->assertRedirect(route('cabinet.index'));
    }

    public function test_settings_manager_returns_defaults_and_persists(): void
    {
        $manager = app(SettingsManager::class);

        $this->assertSame('info@repa.ru', $manager->get('email'));

        $manager->set('email', 'contact@repa.ru');

        $this->assertSame('contact@repa.ru', app(SettingsManager::class)->get('email'));
        $this->assertDatabaseHas('settings', ['key' => 'email', 'value' => 'contact@repa.ru']);
    }

    public function test_footer_uses_settings(): void
    {
        app(SettingsManager::class)->set('email', 'contact@repa.ru');

        $this->get('/')
            ->assertOk()
            ->assertSee('contact@repa.ru');
    }
}
