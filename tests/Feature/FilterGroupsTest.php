<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FilterGroup;
use App\Models\FilterValue;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FilterGroupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_filter_group_on_a_root_category(): void
    {
        $root = Category::factory()->create();

        $group = FilterGroup::factory()->create([
            'category_id' => $root->id,
            'name' => 'Срок созревания',
        ]);

        $this->assertTrue($group->exists);
        $this->assertNotEmpty($group->slug);
    }

    public function test_blocks_a_filter_group_on_a_child_category(): void
    {
        $root = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $root->id]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('корневой категории');

        FilterGroup::factory()->create([
            'category_id' => $child->id,
            'name' => 'Срок созревания',
        ]);
    }

    public function test_blocks_duplicate_slug_within_the_same_category(): void
    {
        $root = Category::factory()->create();
        $first = FilterGroup::factory()->create([
            'category_id' => $root->id,
            'name' => 'Срок созревания',
        ]);

        // spatie/laravel-sluggable сам подставляет суффикс при коллизии,
        // поэтому проверяем явную установку чужого slug (обход Eloquent-хуков
        // — как в CatalogBusinessRulesTest).
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('уже существует');

        $second = FilterGroup::factory()->make([
            'category_id' => $root->id,
            'name' => 'Другое имя',
        ]);
        $second->slug = $first->slug;
        $second->save();
    }

    public function test_allows_same_slug_in_different_categories(): void
    {
        $rootA = Category::factory()->create();
        $rootB = Category::factory()->create();

        FilterGroup::factory()->create([
            'category_id' => $rootA->id,
            'name' => 'Срок созревания',
        ]);

        $group = FilterGroup::factory()->create([
            'category_id' => $rootB->id,
            'name' => 'Срок созревания',
        ]);

        $this->assertTrue($group->exists);
    }

    public function test_deleting_a_group_cascades_to_its_values(): void
    {
        $root = Category::factory()->create();
        $group = FilterGroup::factory()->create(['category_id' => $root->id]);
        FilterValue::factory()->create(['filter_group_id' => $group->id]);

        $group->delete();

        $this->assertSame(0, FilterValue::where('filter_group_id', $group->id)->count());
    }

    public function test_deleting_a_group_detaches_values_from_products(): void
    {
        $root = Category::factory()->create();
        $group = FilterGroup::factory()->create(['category_id' => $root->id]);
        $value = FilterValue::factory()->create(['filter_group_id' => $group->id]);

        $product = Product::factory()->create(['category_id' => $root->id]);
        $product->filterValues()->attach($value->id);

        $this->assertTrue($product->filterValues()->where('filter_values.id', $value->id)->exists());

        $group->delete();

        $this->assertSame(0, $product->filterValues()->count());
    }
}
