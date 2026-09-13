<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionProductFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'collection_id' => Collection::factory(),
            'product_id' => Product::factory(),
            'position' => fake()->numberBetween(0, 100),
        ];
    }
}
