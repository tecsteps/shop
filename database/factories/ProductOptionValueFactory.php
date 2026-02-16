<?php

namespace Database\Factories;

use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductOptionValue> */
class ProductOptionValueFactory extends Factory
{
    protected $model = ProductOptionValue::class;

    public function definition(): array
    {
        return [
            'product_option_id' => ProductOption::factory(),
            'value' => fake()->randomElement(['Small', 'Medium', 'Large', 'Red', 'Blue', 'Green']),
            'position' => 0,
        ];
    }
}
