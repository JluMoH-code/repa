<?php

namespace Tests\Feature\Filament;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_workspace_tabs_filter_publication_state(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $published = Product::factory()->published()->create();
        $draft = Product::factory()->create(['status' => ProductStatus::Draft]);
        Livewire::test(ListProducts::class)
            ->assertSee('Открыть склад')
            ->set('activeTab', 'draft')
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$published]);
    }

    public function test_owner_sees_actionable_overview(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        Livewire::test(Dashboard::class)
            ->assertSuccessful()
            ->assertSee('Обзор магазина')
            ->assertSee('Офлайн-продажа')
            ->assertSee('Требует внимания')
            ->assertSee('Черновики товаров');
    }

    public function test_product_can_be_saved_without_a_manual_page_address(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $category = Category::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Томат для дружелюбной формы',
                'category_id' => $category->id,
                'price' => '80,50',
                'package_size' => '10 шт.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('name', 'Томат для дружелюбной формы')->firstOrFail();
        $this->assertNotEmpty($product->slug);
        $this->assertSame(8050, $product->price);
        $this->assertSame('10 шт.', $product->package_size);
    }
}
