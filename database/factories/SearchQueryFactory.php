<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SearchQuery>
 */
class SearchQueryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'query' => fake()->words(2, true),
            'filters_json' => [],
            'results_count' => fake()->numberBetween(0, 12),
            'created_at' => now(),
        ];
    }
}
