<?php

namespace Database\Factories;

use App\Enums\GrowingPlace;
use App\Enums\ProductStatus;
use App\Enums\RipeningPeriod;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $culture = $this->faker->randomElement(Product::CULTURES);
        $variety = ucfirst($this->faker->word()).' '.$this->faker->numberBetween(1, 999);
        $name = "{$culture} «{$variety}»";

        $price = $this->faker->numberBetween(3000, 25000); // копейки, 30-250 руб.

        return [
            'category_id' => Category::factory(),
            'manufacturer_id' => $this->faker->boolean(80) ? Manufacturer::factory() : null,
            'name' => $name,
            'sku' => 'SKU-'.strtoupper(Str::random(8)),
            'barcode' => $this->faker->boolean(70) ? $this->faker->unique()->ean13() : null,
            'short_description' => $this->faker->optional()->sentence(),
            'description' => $this->faker->optional()->paragraphs(2, true),
            'price' => $price,
            // old_price сознательно не выставляется здесь по умолчанию: значение
            // зависит от итоговой цены, а ->create(['price' => ...]) переопределяет
            // 'price' уже ПОСЛЕ выполнения этого метода — используйте state withDiscount().
            'old_price' => null,
            'unit' => 'упаковка',
            'seed_count' => $this->faker->optional(0.8)->numberBetween(5, 500),
            'status' => $this->faker->randomElement(ProductStatus::cases()),
            'is_active' => true,
            'is_discountable' => true,
            'culture' => $culture,
            'ripening' => $this->faker->optional(0.8)->randomElement(RipeningPeriod::cases()),
            'growing_place' => $this->faker->optional(0.8)->randomElement(GrowingPlace::cases()),
            'is_hybrid' => $this->faker->optional(0.9)->boolean(35),
            'series' => $this->faker->optional(0.3)->word(),
            'attributes' => $this->faker->boolean(60) ? [
                'Цвет' => $this->faker->safeColorName(),
                'Высота растения' => $this->faker->numberBetween(20, 200).' см',
            ] : null,
            'seo_title' => null,
            'seo_description' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Published]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Draft]);
    }

    /**
     * Согласованная пара price/old_price — используется там, где нужен товар со скидкой.
     */
    public function withDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'] ?? $this->faker->numberBetween(3000, 25000);

            return [
                'price' => $price,
                'old_price' => $price + $this->faker->numberBetween(500, 5000),
            ];
        });
    }
}
