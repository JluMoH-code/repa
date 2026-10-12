<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Pages\Orders;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_chips_search_and_status_filter_show_matching_orders(): void
    {
        $new = Order::factory()->create(['status' => OrderStatus::New, 'customer_name' => 'Новая покупательница']);
        $paid = Order::factory()->create(['status' => OrderStatus::Paid, 'customer_name' => 'Оплативший покупатель', 'customer_email' => 'paid@example.com']);
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(Orders::class)->assertSee('Очереди заказов')->assertSee($new->number)->assertDontSee($paid->number)
            ->set('statusFilter', 'paid')->assertSee($paid->number)->assertDontSee($new->number)
            ->set('statusFilter', '')->set('search', 'paid@example.com')->assertSee($paid->number)->assertDontSee($new->number);
    }

    public function test_status_options_match_shipped_and_final_order_rules(): void
    {
        $page = app(Orders::class);
        $shipped = Order::factory()->create(['status' => OrderStatus::Shipped]);
        $delivered = Order::factory()->create(['status' => OrderStatus::Delivered]);
        $this->assertSame([OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Refunded], $page->availableStatuses($shipped));
        $this->assertSame([OrderStatus::Delivered], $page->availableStatuses($delivered));
    }

    public function test_blocked_admin_cannot_render_queue_directly(): void
    {
        $this->actingAs(User::factory()->admin()->create(['is_blocked' => true]));
        Livewire::test(Orders::class)->assertForbidden();
    }
}
