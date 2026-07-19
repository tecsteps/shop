<?php

namespace Database\Factories;

use App\Models\ProductOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProductOptionValue>
 */
class ProductOptionValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_option_id' => ProductOption::factory(),
            'value' => fake()->word(),
            'position' => 0,
        ];
    }
}
