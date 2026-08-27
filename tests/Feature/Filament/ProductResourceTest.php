<?php

namespace Tests\Feature\Filament;

use App\Enums\ProductStatus;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_renders_the_products_list_page(): void
    {
        Product::factory()->count(3)->create([
            'category_id' => Category::factory(),
        ]);

        Livewire::test(ListProducts::class)->assertSuccessful();
    }

    public function test_renders_the_create_product_page(): void
    {
        Livewire::test(CreateProduct::class)->assertSuccessful();
    }

    public function test_renders_the_edit_product_page(): void
    {
        $product = Product::factory()->create(['category_id' => Category::factory()]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_can_create_a_product_through_the_form(): void
    {
        $category = Category::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Огурцы «Апрельские»',
                'slug' => 'ogurtsy-aprelskie',
                'category_id' => $category->id,
                'sku' => 'SKU-FORM-TEST-1',
                'price' => 120,
                'status' => ProductStatus::Draft->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-FORM-TEST-1',
            'category_id' => $category->id,
        ]);
    }

    public function test_price_is_entered_in_rubles_and_stored_in_kopecks(): void
    {
        $category = Category::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Огурцы «Апрельские»',
                'slug' => 'ogurtsy-aprelskie-2',
                'category_id' => $category->id,
                'sku' => 'SKU-FORM-TEST-2',
                'price' => '120,50',
                'old_price' => '149.99',
                'status' => ProductStatus::Draft->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-FORM-TEST-2',
            'price' => 12050,
            'old_price' => 14999,
        ]);
    }

    public function test_price_validation_rejects_non_numeric_value(): void
    {
        $category = Category::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Товар с неверной ценой',
                'slug' => 'tovar-s-nevernoy-cenoy',
                'category_id' => $category->id,
                'sku' => 'SKU-PRICE-INVALID',
                'price' => 'abc',
                'status' => ProductStatus::Draft->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['price']);
    }

    public function test_shows_a_validation_error_for_a_duplicate_sku(): void
    {
        $category = Category::factory()->create();

        Product::factory()->create(['category_id' => $category->id, 'sku' => 'SKU-EXISTING']);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Другой товар',
                'slug' => 'drugoy-tovar',
                'category_id' => $category->id,
                'sku' => 'SKU-EXISTING',
                'price' => 50,
                'status' => ProductStatus::Draft->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['sku']);
    }
}
