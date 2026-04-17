<?php

namespace Database\Factories;

use App\Models\ProductOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductOptionValue>
 */
class ProductOptionValueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_option_id' => ProductOption::factory(),
            'value' => fake()->unique()->word(),
            'position' => 0,
        ];
    }
}
