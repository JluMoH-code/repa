<?php

namespace Database\Factories;

use App\Models\FilterGroup;
use App\Models\FilterValue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FilterValue>
 */
class FilterValueFactory extends Factory
{
    protected $model = FilterValue::class;

    public function definition(): array
    {
        return [
            'filter_group_id' => FilterGroup::factory(),
            'value' => $this->faker->unique()->word(),
            'slug' => Str::slug($this->faker->unique()->word()),
            'sort_order' => 0,
        ];
    }
}
