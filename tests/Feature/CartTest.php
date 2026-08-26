<?php

namespace Tests\Feature;

use App\Actions\Cart\CartManager;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Опубликованный товар в активной категории, в наличии.
     */
    private function product(array $overrides = []): Product
    {
        return Product::factory()->published()->create(array_merge([
            'category_id' => Category::factory()->create(['is_active' => true])->id,
            'price' => 15000,
            'in_stock' => true,
        ], $overrides));
    }

    public function test_guest_can_add_product_to_session_cart(): void
    {
        $product = $this->product();

        $this->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertOk()->assertJson([
            'success' => true,
            'count' => 2,
            'total' => 30000,
            'line_total' => 30000,
        ]);

        $this->assertSame(2, session('cart')[$product->id]);
    }

    public function test_adding_same_product_increments_quantity(): void
    {
        $product = $this->product(['price' => 10000]);

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 3])->assertOk();

        $this->assertSame(5, session('cart')[$product->id]);
    }

    public function test_guest_cannot_add_out_of_stock_product(): void
    {
        $product = $this->product(['in_stock' => false]);

        $this->postJson(route('cart.add'), ['product_id' => $product->id])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertNull(session('cart'));
    }

    public function test_guest_cannot_add_unpublished_product(): void
    {
        $product = Product::factory()->draft()->create([
            'category_id' => Category::factory()->create(['is_active' => true])->id,
            'price' => 15000,
            'in_stock' => true,
        ]);

        $this->postJson(route('cart.add'), ['product_id' => $product->id])
            ->assertStatus(422);
    }

    public function test_guest_cannot_add_product_with_invalid_quantity(): void
    {
        $product = $this->product();

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 0])
            ->assertStatus(422);

        $this->assertNull(session('cart'));
    }

    public function test_guest_can_update_quantity(): void
    {
        $product = $this->product();
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        $this->postJson(route('cart.update'), [
            'product_id' => $product->id,
            'quantity' => 5,
        ])->assertOk()->assertJson([
            'success' => true,
            'count' => 5,
            'total' => 75000,
            'line_total' => 75000,
        ]);

        $this->assertSame(5, session('cart')[$product->id]);
    }

    public function test_guest_cannot_update_quantity_below_minimum(): void
    {
        $product = $this->product();
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        $this->postJson(route('cart.update'), ['product_id' => $product->id, 'quantity' => 0])
            ->assertStatus(422);

        $this->assertSame(2, session('cart')[$product->id]);
    }

    public function test_update_returns_404_for_product_not_in_cart(): void
    {
        $product = $this->product();

        $this->postJson(route('cart.update'), ['product_id' => $product->id, 'quantity' => 3])
            ->assertStatus(404);
    }

    public function test_guest_can_remove_product(): void
    {
        $product = $this->product();
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        $this->postJson(route('cart.remove'), ['product_id' => $product->id])
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 0, 'total' => 0]);

        $this->assertSame([], session('cart'));
    }

    public function test_add_response_includes_product_quantity_in_cart(): void
    {
        $product = $this->product(['price' => 10000]);

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])
            ->assertOk()
            ->assertJson(['quantity' => 2]);

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1])
            ->assertOk()
            ->assertJson(['quantity' => 3]);
    }

    public function test_update_response_includes_product_quantity_in_cart(): void
    {
        $product = $this->product();
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        $this->postJson(route('cart.update'), ['product_id' => $product->id, 'quantity' => 5])
            ->assertOk()
            ->assertJson(['quantity' => 5]);
    }

    public function test_remove_response_includes_zero_quantity(): void
    {
        $product = $this->product();
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        $this->postJson(route('cart.remove'), ['product_id' => $product->id])
            ->assertOk()
            ->assertJson(['quantity' => 0]);
    }

    public function test_cart_manager_reports_quantity_of_product_in_cart(): void
    {
        $product = $this->product();
        $cart = app(CartManager::class);

        $this->assertSame(0, $cart->quantity($product->id));

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 3])->assertOk();
        $this->assertSame(3, $cart->quantity($product->id));

        $this->postJson(route('cart.remove'), ['product_id' => $product->id])->assertOk();
        $this->assertSame(0, $cart->quantity($product->id));
    }

    public function test_quantities_endpoint_returns_actual_cart_state(): void
    {
        $productA = $this->product(['price' => 15000]);
        $productB = $this->product(['price' => 10000]);

        $this->postJson(route('cart.add'), ['product_id' => $productA->id, 'quantity' => 2])->assertOk();
        $this->postJson(route('cart.add'), ['product_id' => $productB->id, 'quantity' => 1])->assertOk();

        $this->getJson(route('cart.quantities'))
            ->assertOk()
            ->assertJson([
                'quantities' => [
                    (string) $productA->id => 2,
                    (string) $productB->id => 1,
                ],
                'count' => 3,
            ]);
    }

    public function test_product_card_shows_quantity_in_cart_instead_of_buy_button(): void
    {
        $product = $this->product(['name' => 'Томаты «Бычье сердце»']);
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        $this->get(route('storefront'))
            ->assertOk()
            ->assertSee('inCart: 2', false)
            ->assertSee('Перейти в корзину');
    }

    public function test_product_card_shows_buy_button_when_product_not_in_cart(): void
    {
        $this->product(['name' => 'Томаты «Бычье сердце»']);

        $this->get(route('storefront'))
            ->assertOk()
            ->assertSee('inCart: 0', false)
            ->assertSee('Купить');
    }

    public function test_product_page_shows_in_cart_state_with_quantity(): void
    {
        $product = $this->product(['name' => 'Томаты «Бычье сердце»']);
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 3])->assertOk();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('inCart: 3', false)
            ->assertSee('В корзине — перейти');
    }

    public function test_guest_can_clear_cart(): void
    {
        $productA = $this->product(['price' => 15000]);
        $productB = $this->product(['price' => 10000]);

        $this->postJson(route('cart.add'), ['product_id' => $productA->id, 'quantity' => 2])->assertOk();
        $this->postJson(route('cart.add'), ['product_id' => $productB->id, 'quantity' => 1])->assertOk();

        $this->postJson(route('cart.clear'))
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 0]);

        $this->assertNull(session('cart'));
    }

    public function test_cart_page_shows_items_and_total(): void
    {
        $productA = $this->product(['price' => 15000, 'name' => 'Томаты «Бычье сердце»']);
        $productB = $this->product(['price' => 10000, 'name' => 'Огурцы «Зозуля»']);

        $this->postJson(route('cart.add'), ['product_id' => $productA->id, 'quantity' => 2])->assertOk();
        $this->postJson(route('cart.add'), ['product_id' => $productB->id, 'quantity' => 1])->assertOk();

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Томаты «Бычье сердце»')
            ->assertSee('Огурцы «Зозуля»')
            ->assertSee('400 ₽'); // 150·2 + 100 = 400 ₽
    }

    public function test_cart_page_shows_empty_state(): void
    {
        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Корзина пуста')
            ->assertSee('Перейти в каталог');
    }

    public function test_authenticated_user_cart_is_stored_in_database(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $this->actingAs($user)->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 3,
        ])->assertOk()->assertJson(['success' => true, 'count' => 3]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->assertNull(session('cart'));
    }

    public function test_authenticated_user_add_increments_single_db_row(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $this->actingAs($user)->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->actingAs($user)->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        $this->assertSame(1, Cart::query()->where('user_id', $user->id)->count());
    }

    public function test_carts_are_scoped_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $productA = $this->product();
        $productB = $this->product();

        $this->actingAs($userA)->postJson(route('cart.add'), ['product_id' => $productA->id, 'quantity' => 1])->assertOk();
        $this->actingAs($userB)->postJson(route('cart.add'), ['product_id' => $productB->id, 'quantity' => 2])->assertOk();

        $this->assertDatabaseHas('carts', ['user_id' => $userA->id, 'product_id' => $productA->id, 'quantity' => 1]);
        $this->assertDatabaseHas('carts', ['user_id' => $userB->id, 'product_id' => $productB->id, 'quantity' => 2]);
    }

    public function test_authenticated_user_cart_page_shows_db_items(): void
    {
        $user = User::factory()->create();
        $product = $this->product(['price' => 5000, 'name' => 'Морковь «Нантская»']);

        $this->actingAs($user)->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 4])->assertOk();

        $this->actingAs($user)->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Морковь «Нантская»')
            ->assertSee('200 ₽'); // 50 · 4 = 200 ₽
    }

    public function test_guest_cart_is_merged_into_user_cart_on_login(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        $this->assertSame(2, session('cart')[$product->id]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->assertNull(session('cart'));
    }

    public function test_login_merges_guest_cart_with_existing_user_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        // У пользователя уже есть позиция (например, с прошлой сессии).
        Cart::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Гость добавляет ещё 1 шт. в сессионную корзину.
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        // Вход — количества складываются в одной строке БД.
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_header_counter_renders_from_session_cart(): void
    {
        $product = $this->product();
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        $this->get(route('storefront'))
            ->assertOk()
            ->assertSee('cart-count');
    }
}
