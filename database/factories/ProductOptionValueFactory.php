<?php

namespace Database\Factories;

use App\Models\ProductOption;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductOptionValueFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'product_option_id' => ProductOption::factory(),
            'value' => fake()->word(),
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
