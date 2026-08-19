<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'label' => $this->faker->randomElement(['10 семян', '20 семян', '50 семян']),
            'price' => $this->faker->numberBetween(3000, 25000),
            'in_stock' => true,
            'sort_order' => 0,
        ];
    }
}
