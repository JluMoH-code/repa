<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_label(): void
    {
        $product = Product::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('название');

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'label' => '',
        ]);
    }

    public function test_blocks_negative_price(): void
    {
        $product = Product::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('отрицательной');

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => -100,
        ]);
    }

    public function test_blocks_product_price_above_max_variant_price(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $product->variants()->create([
            'label' => '10 семян',
            'price' => 8000,
            'in_stock' => true,
            'sort_order' => 0,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('не выше максимальной цены варианта');

        $product->update(['price' => 20000]);
    }

    public function test_allows_product_price_equal_to_min_variant_price(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $product->variants()->create([
            'label' => '10 семян',
            'price' => 10000,
            'in_stock' => true,
            'sort_order' => 0,
        ]);

        $product->variants()->create([
            'label' => '20 семян',
            'price' => 16000,
            'in_stock' => true,
            'sort_order' => 1,
        ]);

        $product->refresh();

        $this->assertSame(2, $product->variants()->count());
    }

    public function test_blocks_old_price_not_above_max_variant_price(): void
    {
        // Цена товара (20000) ниже max варианта (25000) — правило про основную
        // цену не срабатывает; old_price (22000) > цены товара, но <= max
        // варианта — срабатывает именно правило про варианты.
        $product = Product::factory()->create(['price' => 20000, 'old_price' => null]);

        $product->variants()->create([
            'label' => '20 семян',
            'price' => 25000,
            'in_stock' => true,
            'sort_order' => 0,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Старая цена должна быть выше максимальной цены варианта');

        $product->update(['old_price' => 22000]);
    }

    public function test_hard_delete_is_blocked_when_variants_exist(): void
    {
        $product = Product::factory()->draft()->create();
        $product->variants()->create([
            'label' => '10 семян',
            'price' => 10000,
            'in_stock' => true,
            'sort_order' => 0,
        ]);

        $this->assertFalse($product->isHardDeletable());
    }
}
