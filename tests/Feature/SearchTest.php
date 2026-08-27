<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Опубликованный товар в активной категории.
     */
    private function product(array $overrides = []): Product
    {
        return Product::factory()->published()->create(array_merge([
            'category_id' => Category::factory()->create(['is_active' => true])->id,
            'price' => 15000,
            'in_stock' => true,
        ], $overrides));
    }

    public function test_search_finds_product_by_name(): void
    {
        $product = $this->product(['name' => 'Томат «Бычье сердце»']);

        $this->get(route('search', ['q' => 'Томат']))
            ->assertOk()
            ->assertSee('Томат «Бычье сердце»')
            ->assertSee('найдено товаров: 1');
    }

    public function test_search_is_case_insensitive(): void
    {
        // ASCII-регистр (SKU): работает на SQLite (LIKE) и Postgres (ILIKE).
        $product = $this->product(['sku' => 'SKU-ABC123']);

        $this->get(route('search', ['q' => 'sku-abc123']))
            ->assertOk()
            ->assertSee('найдено товаров: 1');
    }

    public function test_search_finds_by_partial_sku(): void
    {
        $product = $this->product(['sku' => 'SKU-ABC123']);

        $this->get(route('search', ['q' => 'abc']))
            ->assertOk()
            ->assertSee('найдено товаров: 1');
    }

    public function test_search_finds_by_short_description(): void
    {
        $product = $this->product(['short_description' => 'Крупноплодный сорт для теплиц']);

        $this->get(route('search', ['q' => 'Крупноплодный']))
            ->assertOk()
            ->assertSee('найдено товаров: 1');
    }

    public function test_search_ignores_unpublished_products(): void
    {
        Product::factory()->draft()->create([
            'category_id' => Category::factory()->create(['is_active' => true])->id,
            'name' => 'Черновик «Секретный»',
        ]);

        $this->get(route('search', ['q' => 'Секретный']))
            ->assertOk()
            ->assertSee('ничего не найдено');
    }

    public function test_search_returns_nothing_for_unknown_query(): void
    {
        $this->product(['name' => 'Томат «Бычье сердце»']);

        $this->get(route('search', ['q' => 'zzzzzz']))
            ->assertOk()
            ->assertSee('ничего не найдено');
    }

    public function test_search_page_without_query_shows_no_results_block(): void
    {
        $this->get(route('search'))
            ->assertOk()
            ->assertSee('Поиск по каталогу')
            ->assertDontSee('найдено товаров');
    }
}
