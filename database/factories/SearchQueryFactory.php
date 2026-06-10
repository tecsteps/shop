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
            'query' => $this->faker->words(2, true),
            'filters_json' => null,
            'results_count' => $this->faker->numberBetween(0, 30),
            'created_at' => $this->faker->dateTimeBetween('-7 days'),
        ];
    }
}
