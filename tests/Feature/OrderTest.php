<?php

namespace Tests\Feature;

use App\Actions\Orders\OrderManager;
use App\Enums\OrderStatus;
use App\Filament\Pages\Orders;
use App\Filament\Pages\OrderShow;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class OrderTest extends TestCase
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

    /**
     * Гостевая корзина: два товара по 150 ₽ и 200 ₽.
     *
     * @return array{Product, Product}
     */
    private function guestCart(): array
    {
        $a = $this->product(['price' => 15000]);
        $b = $this->product(['price' => 20000]);

        $this->postJson(route('cart.add'), ['product_id' => $a->id, 'quantity' => 2])->assertOk();
        $this->postJson(route('cart.add'), ['product_id' => $b->id, 'quantity' => 1])->assertOk();

        return [$a, $b];
    }

    /**
     * Валидные данные формы оформления.
     *
     * @return array<string, string>
     */
    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Иван Петров',
            'customer_email' => 'guest@example.com',
            'customer_phone' => '+7 (999) 123-45-67',
            'delivery_city' => 'Москва',
            'delivery_postcode' => '101000',
            'delivery_address' => 'ул. Цветочная, д. 12, кв. 34',
            'comment' => 'Позвоните после 18:00',
        ], $overrides);
    }

    public function test_guest_cannot_view_checkout_with_empty_cart(): void
    {
        $this->get(route('checkout.create'))
            ->assertRedirect(route('cart.index'));
    }

    public function test_guest_can_view_checkout_with_items(): void
    {
        [$a] = $this->guestCart();

        $this->get(route('checkout.create'))
            ->assertOk()
            ->assertSee('Оформление заказа')
            ->assertSee($a->name)
            ->assertSee('Подтвердить заказ');
    }

    public function test_guest_can_place_order(): void
    {
        [$a, $b] = $this->guestCart();

        $this->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect(route('checkout.success', [
                'order' => Order::query()->firstOrFail()->number,
                'email' => 'guest@example.com',
            ]));

        $this->assertDatabaseHas('orders', [
            'customer_email' => 'guest@example.com',
            'customer_name' => 'Иван Петров',
            'status' => OrderStatus::New->value,
            'user_id' => null,
        ]);

        $order = Order::query()->firstOrFail();

        // Снимок имени и цены на момент заказа.
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $a->id,
            'product_name' => $a->name,
            'price' => 15000,
            'quantity' => 2,
            'line_total' => 30000,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $b->id,
            'price' => 20000,
            'quantity' => 1,
            'line_total' => 20000,
        ]);

        // Гостевая корзина очищена.
        $this->assertNull(session('cart'));
    }

    public function test_guest_order_has_number_and_total_in_kopecks(): void
    {
        $this->guestCart();

        $this->post(route('checkout.store'), $this->checkoutPayload());

        $order = Order::query()->firstOrFail();

        $this->assertMatchesRegularExpression('/^Р-\d{4}-\d{6}$/', $order->number);
        $this->assertSame(50000, $order->subtotal);
        $this->assertSame(50000, $order->total);
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertNotNull($order->placed_at);
    }

    public function test_guest_checkout_validation_rejects_invalid_data(): void
    {
        $this->guestCart();

        $this->from(route('checkout.create'))
            ->post(route('checkout.store'), [
                'customer_name' => '',
                'customer_email' => 'not-an-email',
                'customer_phone' => '',
                'delivery_city' => '',
                'delivery_address' => '',
            ])
            ->assertRedirect(route('checkout.create'))
            ->assertSessionHasErrors(['customer_name', 'customer_email', 'customer_phone', 'delivery_city', 'delivery_address']);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_authenticated_user_checkout_prefilled_from_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Иван Петров',
            'email' => 'ivan@example.com',
            'phone' => '+79991234567',
        ]);
        $product = $this->product();

        $this->actingAs($user)->postJson(route('cart.add'), ['product_id' => $product->id])->assertOk();

        $this->actingAs($user)->get(route('checkout.create'))
            ->assertOk()
            ->assertSee('value="Иван Петров"', false)
            ->assertSee('value="ivan@example.com"', false)
            ->assertSee('value="+79991234567"', false);
    }

    public function test_authenticated_user_can_place_order_from_db_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->product(['price' => 10000]);
        Cart::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 3]);

        $this->actingAs($user)
            ->post(route('checkout.store'), $this->checkoutPayload(['customer_email' => $user->email]))
            ->assertRedirect(route('cabinet.orders.show', Order::query()->firstOrFail()));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'customer_email' => $user->email,
            'total' => 30000,
        ]);

        // Корзина пользователя очищена.
        $this->assertSame(0, Cart::query()->where('user_id', $user->id)->count());
    }

    public function test_order_subtotal_equals_sum_of_line_totals(): void
    {
        [$a, $b] = $this->guestCart();

        $this->post(route('checkout.store'), $this->checkoutPayload());

        $order = Order::query()->firstOrFail();
        $sum = $order->items->sum('line_total');

        $this->assertSame($order->subtotal, $sum);
        $this->assertSame($order->total, $sum);
    }

    public function test_orders_list_requires_auth(): void
    {
        $this->get(route('cabinet.orders'))
            ->assertRedirect(route('login'));
    }

    public function test_orders_list_shows_only_own_orders(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $orderA = Order::factory()->forUser($userA)->create();
        $orderB = Order::factory()->forUser($userB)->create();

        $this->actingAs($userA)->get(route('cabinet.orders'))
            ->assertOk()
            ->assertSee($orderA->number)
            ->assertDontSee($orderB->number);
    }

    public function test_guest_order_becomes_visible_after_login_by_email(): void
    {
        $user = User::factory()->create(['email' => 'guest@example.com']);
        $this->guestCart();

        $this->post(route('checkout.store'), $this->checkoutPayload());
        $order = Order::query()->firstOrFail();

        $this->actingAs($user)->get(route('cabinet.orders'))
            ->assertOk()
            ->assertSee($order->number);
    }

    public function test_order_show_returns_404_for_foreign_order(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::factory()->forUser($other)->create();

        $this->actingAs($user)
            ->get(route('cabinet.orders.show', $order))
            ->assertNotFound();
    }

    public function test_order_show_renders_items_and_status_badge(): void
    {
        $user = User::factory()->create();
        $product = $this->product(['name' => 'Огурец «Зозуля F1»']);
        $order = Order::factory()->forUser($user)->create();
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 15000,
            'quantity' => 2,
            'line_total' => 30000,
        ]);

        $this->actingAs($user)->get(route('cabinet.orders.show', $order))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee('Огурец «Зозуля F1»')
            ->assertSee('Новый') // статус-бейдж
            ->assertSee('300 ₽');
    }

    public function test_admin_orders_page_is_accessible_to_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('filament.admin.pages.orders'))
            ->assertSuccessful();
    }

    public function test_admin_orders_page_is_blocked_for_customer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('filament.admin.pages.orders'))
            ->assertRedirect(route('cabinet.index'));
    }

    public function test_admin_can_change_order_status_via_filament_page(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create();

        $this->actingAs($admin);

        Livewire::test(Orders::class)
            ->call('changeStatus', $order->id, OrderStatus::Processing->value);

        $this->assertSame(OrderStatus::Processing, $order->refresh()->status);
    }

    public function test_order_show_page_in_admin_renders_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create();

        $this->actingAs($admin);

        Livewire::test(OrderShow::class, ['order' => $order->number])
            ->assertSuccessful()
            ->assertSet('order.number', $order->number);
    }

    public function test_order_cannot_be_returned_to_new_status(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Processing]);

        $order->status = OrderStatus::New;

        $this->expectException(InvalidArgumentException::class);
        $order->save();
    }

    public function test_delivered_order_cannot_change_status(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Delivered]);

        $order->status = OrderStatus::Processing;

        $this->expectException(InvalidArgumentException::class);
        $order->save();
    }

    public function test_order_number_is_unique(): void
    {
        $orders = collect(range(1, 5))->map(fn () => Order::factory()->create());

        $this->assertSame(5, $orders->pluck('number')->unique()->count());
        $this->assertSame(5, Order::query()->count());
    }

    public function test_order_manager_creates_order_from_session_cart(): void
    {
        [$a] = $this->guestCart();

        $order = app(OrderManager::class)->createFromCart(
            customer: [
                'customer_name' => 'Иван Петров',
                'customer_email' => 'guest@example.com',
                'customer_phone' => '+7 (999) 123-45-67',
            ],
            delivery: [
                'delivery_city' => 'Москва',
                'delivery_address' => 'ул. Цветочная, 12',
            ],
        );

        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertSame(2, $order->items()->count());
        $this->assertSame($a->name, $order->items()->orderBy('id')->first()->product_name);
        $this->assertNull(session('cart'));
    }

    public function test_guest_success_page_works_with_order_number_and_email(): void
    {
        $this->guestCart();
        $this->post(route('checkout.store'), $this->checkoutPayload());
        $order = Order::query()->firstOrFail();

        $this->get(route('checkout.success', ['order' => $order->number, 'email' => 'guest@example.com']))
            ->assertOk()
            ->assertSee('Спасибо за заказ!')
            ->assertSee($order->number);
    }

    public function test_guest_success_page_rejects_wrong_email(): void
    {
        $this->guestCart();
        $this->post(route('checkout.store'), $this->checkoutPayload());
        $order = Order::query()->firstOrFail();

        $this->get(route('checkout.success', ['order' => $order->number, 'email' => 'hacker@example.com']))
            ->assertRedirect(route('storefront'));
    }

    public function test_order_number_is_generated_on_create_without_number(): void
    {
        $order = Order::query()->create([
            'customer_name' => 'Иван',
            'customer_email' => 'ivan@example.com',
            'customer_phone' => '+79991234567',
            'delivery_city' => 'Москва',
            'delivery_address' => 'ул. Ленина, 1',
            'status' => OrderStatus::New,
            'subtotal' => 10000,
            'total' => 10000,
            'placed_at' => now(),
        ]);

        $this->assertMatchesRegularExpression('/^Р-\d{4}-\d{6}$/', $order->number);
    }
}
