<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CatalogBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_product_with_valid_data(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Томаты «Бычье сердце»',
            'sku' => 'SKU-VALID-001',
            'price' => 15000,
        ]);

        $this->assertTrue($product->exists);
        $this->assertNotEmpty($product->slug);
        $this->assertSame('SKU-VALID-001', $product->sku);
    }

    public function test_blocks_creating_a_product_with_a_duplicate_sku(): void
    {
        $category = Category::factory()->create();

        Product::factory()->create(['category_id' => $category->id, 'sku' => 'SKU-DUPLICATE']);

        $this->expectException(QueryException::class);

        Product::factory()->create(['category_id' => $category->id, 'sku' => 'SKU-DUPLICATE']);
    }

    public function test_blocks_creating_a_product_with_a_duplicate_slug(): void
    {
        $category = Category::factory()->create();

        $first = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Уникальное имя товара',
        ]);

        $this->expectException(QueryException::class);

        // spatie/laravel-sluggable перегенерирует slug из name на каждом create(),
        // поэтому уникальность на уровне БД проверяем прямой вставкой в обход Eloquent-хуков.
        $duplicate = Product::factory()->make([
            'category_id' => $category->id,
        ])->toArray();
        $duplicate['slug'] = $first->slug;
        $duplicate['attributes'] = null;

        \Illuminate\Support\Facades\DB::table('products')->insert($duplicate);
    }

    public function test_blocks_creating_a_product_with_a_negative_price(): void
    {
        $category = Category::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        Product::factory()->create([
            'category_id' => $category->id,
            'price' => -100,
        ]);
    }

    public function test_blocks_old_price_that_is_not_greater_than_price(): void
    {
        $category = Category::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 10000,
            'old_price' => 9000,
        ]);
    }

    public function test_blocks_publishing_a_product_in_an_inactive_category(): void
    {
        $category = Category::factory()->inactive()->create();

        $this->expectException(InvalidArgumentException::class);

        Product::factory()->create([
            'category_id' => $category->id,
            'status' => ProductStatus::Published,
        ]);
    }

    public function test_allows_a_draft_product_in_an_inactive_category(): void
    {
        $category = Category::factory()->inactive()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'status' => ProductStatus::Draft,
        ]);

        $this->assertTrue($product->exists);
    }

    public function test_blocks_deleting_a_category_that_has_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $this->expectException(InvalidArgumentException::class);

        try {
            $category->delete();
        } finally {
            $this->assertNotNull(Category::find($category->id));
        }
    }

    public function test_allows_deleting_an_empty_category(): void
    {
        $category = Category::factory()->create();

        $category->delete();

        $this->assertNull(Category::find($category->id));
    }

    public function test_archiving_a_product_does_not_delete_its_row(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->published()->create(['category_id' => $category->id]);

        $product->update(['status' => ProductStatus::Archived]);

        $fresh = Product::find($product->id);

        $this->assertNotNull($fresh);
        $this->assertSame(ProductStatus::Archived, $fresh->status);
    }

    public function test_builds_category_breadcrumbs_from_root_to_current(): void
    {
        $root = Category::factory()->create(['name' => 'Овощи']);
        $child = Category::factory()->create(['name' => 'Томаты', 'parent_id' => $root->id]);

        $breadcrumbs = $child->breadcrumbs()->pluck('name')->all();

        $this->assertSame(['Овощи', 'Томаты'], $breadcrumbs);
    }
}
