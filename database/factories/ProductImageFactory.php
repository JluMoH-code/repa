<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'path' => 'products/placeholders/'.$this->faker->numberBetween(1, 12).'.svg',
            'sort_order' => 0,
            'is_main' => false,
        ];
    }

    public function main(): static
    {
        return $this->state(fn () => ['is_main' => true]);
    }
}
