<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_renders_the_categories_list_page(): void
    {
        Category::factory()->count(3)->create();

        Livewire::test(ListCategories::class)->assertSuccessful();
    }

    public function test_can_create_a_category_and_a_subcategory(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Овощи',
                'slug' => 'ovoshchi',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $parent = Category::where('slug', 'ovoshchi')->firstOrFail();

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Томаты',
                'slug' => 'tomaty',
                'parent_id' => $parent->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'slug' => 'tomaty',
            'parent_id' => $parent->id,
        ]);
    }
}
