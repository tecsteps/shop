<?php

namespace Database\Factories;

use App\Models\SearchQuery;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchQuery>
 */
class SearchQueryFactory extends Factory
{
    protected $model = SearchQuery::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'query' => fake()->word(),
            'results_count' => 0,
        ];
    }
}
