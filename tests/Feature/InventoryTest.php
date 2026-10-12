<?php

namespace Tests\Feature;

use App\Actions\Cart\CartManager;
use App\Actions\Inventory\InventoryManager;
use App\Actions\Orders\OrderManager;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Filament\Pages\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    public function test_inventory_dispatches_browser_modal_open_and_close_events(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        Livewire::test(Inventory::class)
            ->call('open', 'receive')
            ->assertDispatched('open-modal', id: 'inventory-operation')
            ->call('close')
            ->assertDispatched('close-modal', id: 'inventory-operation');
    }

    use RefreshDatabase;

    public function test_receipt_and_offline_sale_preserve_reserved_stock_and_record_actor(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);
        $product = Product::factory()->create();
        $inventory = app(InventoryManager::class);
        $inventory->receive($product, 10);
        $inventory->offlineSale($product, 3, 12500);
        $this->assertSame(7, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', ['type' => 'offline_sale', 'quantity_change' => -3, 'unit_price' => 12500, 'user_id' => $admin->id]);
    }

    public function test_order_reserves_then_commits_stock_exactly_once(): void
    {
        $product = Product::factory()->published()->create(['stock_quantity' => 10]);
        app(CartManager::class)->add($product, 3);
        $orders = app(OrderManager::class);
        $order = $orders->createFromCart(['customer_name' => 'Иван', 'customer_email' => 'ivan@example.test', 'customer_phone' => '+79999999999'], []);
        $this->assertSame(3, $product->fresh()->reserved_quantity);
        $orders->changeStatus($order, OrderStatus::Shipped);
        $orders->changeStatus($order, OrderStatus::Shipped);
        $this->assertSame(7, $product->fresh()->stock_quantity);
        $this->assertSame(0, $product->fresh()->reserved_quantity);
    }

    public function test_two_variants_are_distinct_cart_lines_with_their_prices(): void
    {
        $product = Product::factory()->published()->create();
        $a = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 10000]);
        $b = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 20000]);
        $cart = app(CartManager::class);
        $cart->add($product, 2, $a);
        $cart->add($product, 1, $b);
        $this->assertCount(2, $cart->lines());
        $this->assertSame(40000, $cart->total());
        $cart->remove($product->id, $a->id);
        $this->assertCount(1, $cart->lines());
    }

    public function test_omitted_variant_resolves_to_real_stock_and_legacy_cart_is_normalized(): void
    {
        $product = Product::factory()->published()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 12300, 'stock_quantity' => 1]);
        $response = $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 99]);
        $response->assertStatus(422);
        app(CartManager::class)->clear();
        session(['cart' => [$product->id => 1]]);
        $cart = app(CartManager::class);
        $this->assertSame($variant->id, $cart->lines()->first()['variant']->id);
        $this->assertSame(12300, $cart->total());
        $cart->remove($product->id, $variant->id);
        $this->assertSame(0, $cart->count());
    }

    public function test_shipped_order_cannot_reopen_for_quantity_edits(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Shipped]);
        $this->expectException(\InvalidArgumentException::class);
        app(OrderManager::class)->changeStatus($order, OrderStatus::Processing);
    }

    public function test_cancel_releases_reservation_and_quantity_edits_are_atomic(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $product = Product::factory()->published()->create(['stock_quantity' => 10]);
        $cart = app(CartManager::class);
        $cart->add($product, 3);
        $orders = app(OrderManager::class);
        $order = $orders->createFromCart(['customer_name' => 'Иван', 'customer_email' => 'ivan@example.test', 'customer_phone' => '+79999999999'], []);
        $orders->updateItemQuantities($order, [$order->items->first()->id => 5]);
        $this->assertSame(5, $product->fresh()->reserved_quantity);
        try {
            $orders->updateItemQuantities($order, [$order->items->first()->id => 11]);
            $this->fail('Oversell must fail');
        } catch (ValidationException) {
        }
        $this->assertSame(5, $product->fresh()->reserved_quantity);
        $this->assertSame(5, $order->items()->first()->quantity);
        $orders->changeStatus($order, OrderStatus::Cancelled);
        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertSame(0, $product->fresh()->reserved_quantity);
    }

    public function test_stock_adjustments_require_active_admin_and_reject_reserved_offline_sale(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $product = Product::factory()->create(['stock_quantity' => 5, 'reserved_quantity' => 4]);
        $this->actingAs($admin);
        try {
            app(InventoryManager::class)->offlineSale($product, 2, 10000);
            $this->fail('Reserved stock must not sell');
        } catch (ValidationException) {
        }
        $this->assertSame(5, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_movements', 0);
        $admin->update(['is_blocked' => true]);
        $this->expectException(HttpException::class);
        app(InventoryManager::class)->receive($product, 1);
    }

    public function test_inventory_page_records_decimal_ruble_sale_and_rejects_invalid_input(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $product = Product::factory()->create(['stock_quantity' => 5]);
        Livewire::test(Inventory::class)
            ->call('open', 'offline_sale', $product->id)
            ->set('quantity', '2')->set('unitPrice', '80,50')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $product->id, 'unit_price' => 8050, 'quantity_change' => -2]);
        Livewire::test(Inventory::class)
            ->call('open', 'offline_sale', $product->id)
            ->set('quantity', '1')->set('unitPrice', '80.505')->call('save')->assertHasErrors(['unitPrice']);
        $this->assertSame(3, $product->fresh()->stock_quantity);
    }

    public function test_inventory_manage_starts_unknown_stock_count_and_switches_operation(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $product = Product::factory()->create();
        Livewire::test(Inventory::class)
            ->call('manage', $product->id)
            ->assertSet('operation', 'correct')->assertSet('modalOpen', true)
            ->set('quantity', '12')->call('save')->assertHasNoErrors()
            ->call('manage', $product->id)
            ->assertSet('operation', 'receive')
            ->call('switchOperation', 'offline_sale')
            ->set('quantity', '2')->set('unitPrice', '80,50')->call('save')->assertHasNoErrors();
        $this->assertSame(10, $product->fresh()->stock_quantity);
    }

    public function test_inventory_unknown_filter_shows_only_real_variant_positions(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $product = Product::factory()->create(['name' => 'Товар с фасовками', 'stock_quantity' => null]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'label' => 'Известная фасовка', 'stock_quantity' => 20]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'label' => 'Неизвестная фасовка', 'stock_quantity' => null]);
        Livewire::test(Inventory::class)
            ->call('setFilter', 'unknown')
            ->assertSee('Неизвестная фасовка')->assertDontSee('Известная фасовка')
            ->assertDontSee('Основная упаковка')
            ->assertSee('Начать учёт');
    }

    public function test_inventory_modal_search_limits_options_and_preserves_selected_variant(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        Product::factory()->count(31)->create();
        $selected = Product::factory()->create(['name' => 'ZZZ выбранный товар']);
        $variant = ProductVariant::factory()->create(['product_id' => $selected->id, 'stock_quantity' => 12]);
        $found = Product::factory()->create(['sku' => 'CASE-SEARCH-123']);
        Livewire::test(Inventory::class)
            ->call('open', 'receive', $selected->id, $variant->id)
            ->assertViewHas('options', fn ($options) => $options->count() <= 30 && $options->contains('id', $selected->id))
            ->set('modalProductSearch', 'case-search')
            ->assertViewHas('options', fn ($options) => $options->count() === 2 && $options->contains('id', $found->id) && $options->contains('id', $selected->id))
            ->assertSet('variantId', $variant->id)
            ->call('close')->assertSet('modalProductSearch', '');
    }

    public function test_inventory_target_switch_does_not_keep_previous_count(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $product = Product::factory()->create();
        $a = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 30]);
        $b = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 8]);
        $other = Product::factory()->create(['stock_quantity' => 20]);
        Livewire::test(Inventory::class)
            ->call('open', 'correct', $product->id, $a->id)->assertSet('quantity', '30')
            ->set('quantity', '35')->set('variantId', $b->id)->assertSet('quantity', '8')
            ->set('productId', $other->id)->assertSet('quantity', '20')
            ->call('switchOperation', 'receive')->set('quantity', '5')
            ->set('productId', $product->id)->assertSet('quantity', '');
    }

    public function test_foreign_variant_is_rejected_and_variant_checkout_snapshots_price(): void
    {
        $product = Product::factory()->published()->create();
        $foreign = ProductVariant::factory()->create();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'variant_id' => $foreign->id])->assertStatus(422);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 12345, 'stock_quantity' => 10]);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2])->assertOk()->assertJsonPath('quantity', 2)->assertJsonPath('line_total', 24690);
        $order = app(OrderManager::class)->createFromCart(['customer_name' => 'Иван', 'customer_email' => 'ivan@example.test', 'customer_phone' => '+79999999999'], []);
        $item = $order->items->first();
        $this->assertSame($variant->id, $item->product_variant_id);
        $this->assertSame($variant->label, $item->variant_label);
        $this->assertSame(12345, $item->price);
        $this->assertSame(2, $variant->fresh()->reserved_quantity);
        $this->assertNull($product->fresh()->stock_quantity);
    }

    public function test_direct_delivery_commits_and_shipped_refund_does_not_restock(): void
    {
        $product = Product::factory()->published()->create(['stock_quantity' => 10]);
        app(CartManager::class)->add($product, 2);
        $manager = app(OrderManager::class);
        $customer = ['customer_name' => 'Иван', 'customer_email' => 'ivan@example.test', 'customer_phone' => '+79999999999'];
        $order = $manager->createFromCart($customer, []);
        $manager->changeStatus($order, OrderStatus::Delivered);
        $this->assertSame(8, $product->fresh()->stock_quantity);
        app(CartManager::class)->add($product->fresh(), 2);
        $order = $manager->createFromCart($customer, []);
        $manager->changeStatus($order, OrderStatus::Shipped);
        $manager->changeStatus($order, OrderStatus::Refunded);
        $this->assertSame(6, $product->fresh()->stock_quantity);
        $this->assertSame(0, $product->fresh()->reserved_quantity);
    }

    public function test_historical_orders_do_not_deduct_and_failed_checkout_preserves_cart(): void
    {
        $product = Product::factory()->published()->create(['stock_quantity' => 5]);
        $order = Order::factory()->create();
        $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 3, 'price' => $product->price, 'line_total' => $product->price * 3]);
        app(OrderManager::class)->changeStatus($order, OrderStatus::Shipped);
        $this->assertSame(5, $product->fresh()->stock_quantity);
        app(CartManager::class)->add($product, 3);
        $product->update(['reserved_quantity' => 4]);
        try {
            app(OrderManager::class)->createFromCart(['customer_name' => 'Иван', 'customer_email' => 'ivan@example.test', 'customer_phone' => '+79999999999'], []);
            $this->fail('Checkout must fail');
        } catch (ValidationException) {
        }
        $this->assertSame(3, app(CartManager::class)->count());
        $this->assertDatabaseCount('orders', 1);
    }
}
