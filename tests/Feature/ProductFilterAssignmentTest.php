<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\FilterGroup;
use App\Models\FilterValue;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ProductFilterAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function makeValueForRoot(Category $root): FilterValue
    {
        $group = FilterGroup::factory()->create(['category_id' => $root->id]);

        return FilterValue::factory()->create(['filter_group_id' => $group->id]);
    }

    public function test_assigns_a_value_from_the_products_root_category(): void
    {
        $root = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $root->id]);
        $value = $this->makeValueForRoot($root);

        $product = Product::factory()->create(['category_id' => $child->id]);
        $product->filterValues()->attach($value->id);

        $this->assertTrue($product->filterValues()->where('filter_values.id', $value->id)->exists());
    }

    public function test_blocks_a_value_from_a_foreign_root_category(): void
    {
        $rootA = Category::factory()->create();
        $rootB = Category::factory()->create();
        $foreignValue = $this->makeValueForRoot($rootB);

        $product = Product::factory()->create(['category_id' => $rootA->id, 'status' => ProductStatus::Published]);

        $product->filterValues()->attach($foreignValue->id);

        // Attach не вызывает saving-событие товара — guard срабатывает при
        // следующем сохранении товара (как в форме Filament).
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('корневой категории');

        $product->save();
    }

    public function test_category_change_clears_assigned_filter_values(): void
    {
        $rootA = Category::factory()->create();
        $rootB = Category::factory()->create();
        $value = $this->makeValueForRoot($rootA);

        $product = Product::factory()->create(['category_id' => $rootA->id]);
        $product->filterValues()->attach($value->id);

        $product->update(['category_id' => $rootB->id]);

        $this->assertSame(0, $product->filterValues()->count());
    }

    public function test_filter_option_groups_returns_groups_of_the_root_category(): void
    {
        $root = Category::factory()->create();
        $otherRoot = Category::factory()->create();

        $ownGroup = FilterGroup::factory()->create(['category_id' => $root->id]);
        FilterValue::factory()->create(['filter_group_id' => $ownGroup->id]);
        FilterGroup::factory()->create(['category_id' => $otherRoot->id]);

        $child = Category::factory()->create(['parent_id' => $root->id]);
        $product = Product::factory()->create(['category_id' => $child->id]);

        $groups = $product->filterOptionGroups();

        $this->assertCount(1, $groups);
        $this->assertArrayHasKey($ownGroup->name, $groups);
    }
}
