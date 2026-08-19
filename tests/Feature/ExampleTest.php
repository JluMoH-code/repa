<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Главная страница витрины отдаёт 200 и показывает каталог.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        Product::factory()->published()->create(['category_id' => $category->id]);

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Хиты продаж');
    }
}
