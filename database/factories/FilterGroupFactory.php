<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\FilterGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FilterGroup>
 */
class FilterGroupFactory extends Factory
{
    protected $model = FilterGroup::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => $this->faker->unique()->word(),
            'slug' => Str::slug($this->faker->unique()->word()),
            'sort_order' => 0,
        ];
    }
}
