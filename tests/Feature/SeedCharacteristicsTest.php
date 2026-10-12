<?php

namespace Tests\Feature;

use App\Enums\GrowingPlace;
use App\Enums\ProductStatus;
use App\Enums\RipeningPeriod;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedCharacteristicsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_characteristics_are_shown_without_an_optional_sku(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Published,
            'is_active' => true,
            'sku' => null,
            'package_size' => '0,5 г',
            'seed_count' => 20,
            'ripening' => RipeningPeriod::Early,
            'growing_place' => GrowingPlace::Universal,
            'is_hybrid' => false,
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk()
            ->assertSee('0,5 г')
            ->assertSee(RipeningPeriod::Early->getLabel())
            ->assertSee('Теплица и открытый грунт')
            ->assertSee('Нет')
            ->assertDontSee('Артикул')
            ->assertSee('Возврат семян');

        $characteristics = collect($response->viewData('characteristics'));
        $this->assertSame('0,5 г', $characteristics->firstWhere('label', 'Вес, метраж или количество семян')['value']);
    }

    public function test_existing_seed_count_and_hybrid_are_shown(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Published,
            'is_active' => true,
            'package_size' => null,
            'seed_count' => 20,
            'is_hybrid' => true,
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('20 шт.')
            ->assertSee('Да (F1)')
            ->assertSee($product->sku);
    }
}
