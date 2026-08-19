<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoritesTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $overrides = []): Product
    {
        return Product::factory()->published()->create(array_merge([
            'category_id' => Category::factory()->create(['is_active' => true])->id,
            'price' => 15000,
            'in_stock' => true,
        ], $overrides));
    }

    public function test_guest_can_toggle_favorite_in_session(): void
    {
        $product = $this->product();

        $this->postJson(route('favorites.toggle'), ['product_id' => $product->id])
            ->assertOk()
            ->assertJson(['success' => true, 'favorite' => true, 'count' => 1]);

        $this->assertSame(true, session('favorites')[$product->id]);

        // Повторный toggle — удаляет из избранного.
        $this->postJson(route('favorites.toggle'), ['product_id' => $product->id])
            ->assertOk()
            ->assertJson(['success' => true, 'favorite' => false, 'count' => 0]);
    }

    public function test_guest_cannot_toggle_invalid_product(): void
    {
        $this->postJson(route('favorites.toggle'), ['product_id' => 999999])
            ->assertStatus(422);

        $this->assertNull(session('favorites'));
    }

    public function test_authenticated_user_favorite_is_stored_in_database(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $this->actingAs($user)->postJson(route('favorites.toggle'), ['product_id' => $product->id])
            ->assertOk()
            ->assertJson(['favorite' => true, 'count' => 1]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_authenticated_user_can_remove_favorite(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $this->actingAs($user)->postJson(route('favorites.toggle'), ['product_id' => $product->id])->assertOk();

        $this->actingAs($user)->postJson(route('favorites.remove'), ['product_id' => $product->id])
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 0]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_guest_favorites_are_merged_on_login(): void
    {
        $user = User::factory()->create();
        $productA = $this->product();
        $productB = $this->product();

        $this->postJson(route('favorites.toggle'), ['product_id' => $productA->id])->assertOk();
        $this->postJson(route('favorites.toggle'), ['product_id' => $productB->id])->assertOk();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'product_id' => $productA->id]);
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'product_id' => $productB->id]);
        $this->assertNull(session('favorites'));
    }

    public function test_login_merges_guest_favorites_without_duplicates(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        // У пользователя уже есть товар в избранном.
        Favorite::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        // Гость добавляет тот же товар и ещё один.
        $this->postJson(route('favorites.toggle'), ['product_id' => $product->id])->assertOk();
        $another = $this->product();
        $this->postJson(route('favorites.toggle'), ['product_id' => $another->id])->assertOk();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertSame(1, Favorite::query()->where('user_id', $user->id)->where('product_id', $product->id)->count());
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'product_id' => $another->id]);
    }

    public function test_favorites_page_shows_products(): void
    {
        $user = User::factory()->create();
        $product = $this->product(['name' => 'Огурец «Зозуля F1»']);

        $this->actingAs($user)->postJson(route('favorites.toggle'), ['product_id' => $product->id])->assertOk();

        $this->actingAs($user)->get(route('cabinet.favorites'))
            ->assertOk()
            ->assertSee('Огурец «Зозуля F1»')
            ->assertSee('В корзину');
    }

    public function test_favorites_page_is_available_only_for_authenticated_users(): void
    {
        $this->get(route('cabinet.favorites'))
            ->assertRedirect(route('login'));
    }
}
