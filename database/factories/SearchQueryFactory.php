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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'query' => fake()->word(),
            'filters_json' => null,
            'results_count' => 0,
            'session_id' => null,
            'created_at' => now(),
        ];
    }
}
