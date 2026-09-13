<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class SearchQueryFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'query' => fake()->words(2, true),
            'filters_json' => null,
            'results_count' => fake()->numberBetween(0, 500),
        ];
    }
}
