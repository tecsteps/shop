<?php

namespace Database\Factories;

use App\Models\SearchQuery;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SearchQuery>
 */
class SearchQueryFactory extends Factory
{
    protected $model = SearchQuery::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'query' => fake()->words(2, true),
            'filters_json' => ['in_stock' => true],
            'results_count' => fake()->numberBetween(0, 40),
            'created_at' => now(),
        ];
    }
}
