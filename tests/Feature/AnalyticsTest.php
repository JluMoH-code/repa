<?php

namespace Tests\Feature;

use App\Actions\Analytics\AnalyticsSummary;
use App\Actions\Analytics\RecordStorefrontEvent;
use App\Enums\UserRole;
use App\Filament\Pages\Analytics;
use App\Models\AnalyticsEvent;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_views_record_anonymous_sessions_without_query_or_private_source(): void
    {
        $this->get('/?utm_source=mail%40example.com&email=private@example.com')->assertOk();
        $this->get('/')->assertOk();
        $events = AnalyticsEvent::all();
        $this->assertCount(2, $events);
        $this->assertSame('/', $events[0]->path);
        $this->assertSame('direct', $events[0]->source);
        $this->assertSame($events[0]->visitor_token, $events[1]->visitor_token);
        $this->assertSame(64, strlen($events[0]->visitor_token));
    }

    public function test_dnt_admin_and_failed_requests_are_excluded(): void
    {
        $this->withHeader('DNT', '1')->get('/')->assertOk();
        $this->flushHeaders();
        $this->get('/product/nonexistent')->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))->get('/')->assertOk();
        $this->assertDatabaseCount('analytics_events', 0);
    }

    public function test_summary_counts_delivered_sales_and_unique_purchasing_sessions(): void
    {
        Order::factory()->create(['status' => 'delivered', 'total' => 12345, 'placed_at' => now()]);
        Order::factory()->create(['status' => 'cancelled', 'total' => 90000, 'placed_at' => now()]);
        foreach (['page_view', 'page_view', 'order', 'order'] as $type) {
            AnalyticsEvent::create(['event_type' => $type, 'visitor_token' => str_repeat('a', 64), 'path' => '/', 'source' => 'direct']);
        }
        AnalyticsEvent::create(['event_type' => 'page_view', 'visitor_token' => str_repeat('b', 64), 'path' => '/', 'source' => 'direct']);
        $summary = app(AnalyticsSummary::class)->forPeriod('today');
        $this->assertSame(12345, $summary['online_revenue']);
        $this->assertSame(1, $summary['orders_count']);
        $this->assertSame(12345, $summary['average_order']);
        $this->assertSame(2, $summary['visitors']);
        $this->assertSame(3, $summary['page_views']);
        $this->assertSame(50.0, $summary['conversion']);
    }

    public function test_empty_traffic_has_no_conversion(): void
    {
        $this->assertNull(app(AnalyticsSummary::class)->forPeriod()['conversion']);
    }

    public function test_cart_and_checkout_events_only_record_successes(): void
    {
        $product = Product::factory()->published()->create(['category_id' => Category::factory()->create(['is_active' => true])->id, 'in_stock' => true]);
        $this->postJson('/cart/add', ['product_id' => 999999])->assertStatus(422);
        $this->post('/checkout', [])->assertSessionHasErrors();
        $this->assertDatabaseCount('analytics_events', 0);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->post('/checkout', ['customer_name' => 'Customer', 'customer_email' => 'customer@example.com', 'customer_phone' => '+79991234567', 'delivery_method' => 'pickup'])->assertRedirect();
        $this->assertDatabaseHas('analytics_events', ['event_type' => 'cart_add', 'path' => '/cart/add']);
        $this->assertDatabaseHas('analytics_events', ['event_type' => 'order', 'path' => '/checkout']);
        $this->assertDatabaseCount('analytics_events', 2);
    }

    public function test_product_tracking_and_referrer_only_store_public_path_and_hostname(): void
    {
        $product = Product::factory()->published()->create(['category_id' => Category::factory()->create(['is_active' => true])->id]);
        $this->withHeader('referer', 'https://example.org/private?email=private@example.com')->get(route('products.show', $product))->assertOk();
        $this->assertDatabaseHas('analytics_events', ['event_type' => 'page_view', 'product_id' => $product->id, 'source' => 'example.org']);
    }

    public function test_recording_failure_does_not_break_storefront_and_reports_safe_context(): void
    {
        $this->mock(RecordStorefrontEvent::class)->shouldReceive('record')->once()->andThrow(new \RuntimeException('private payload'));
        Log::shouldReceive('warning')->once()->with('Storefront analytics event could not be saved.', ['event_type' => 'page_view', 'exception_class' => \RuntimeException::class]);
        $this->get('/')->assertOk();
    }

    public function test_analytics_page_rejects_customer_and_renders_for_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/analytics')->assertRedirect();
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))->get('/admin/analytics')->assertOk()->assertSee('Источники посещений');
    }

    public function test_direct_livewire_rendering_rejects_blocked_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin, 'is_blocked' => true]));
        Livewire::test(Analytics::class)->assertForbidden();
    }

    public function test_period_bounds_and_offline_sales_use_real_signed_movements(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(12));
        $product = Product::factory()->create();
        InventoryMovement::create(['product_id' => $product->id, 'type' => 'offline_sale', 'quantity_change' => -3, 'unit_price' => 1250]);
        InventoryMovement::create(['product_id' => $product->id, 'type' => 'receipt', 'quantity_change' => 20, 'unit_price' => 900]);
        Order::factory()->create(['status' => 'delivered', 'total' => 5000, 'placed_at' => now()->subDays(6)->startOfDay()]);
        Order::factory()->create(['status' => 'delivered', 'total' => 9000, 'placed_at' => now()->subDays(7)->endOfDay()]);
        $today = app(AnalyticsSummary::class)->forPeriod('today');
        $week = app(AnalyticsSummary::class)->forPeriod('7d');
        $this->assertSame(3750, $today['offline_revenue']);
        $this->assertSame(0, $today['online_revenue']);
        $this->assertSame(5000, $week['online_revenue']);
        $this->assertSame(8750, $week['revenue']);
    }

    public function test_invalid_period_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(AnalyticsSummary::class)->forPeriod('all');
    }
}
