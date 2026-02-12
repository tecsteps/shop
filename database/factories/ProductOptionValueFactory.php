<?php

namespace Database\Factories;

use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductOptionValue> */
class ProductOptionValueFactory extends Factory
{
    protected $model = ProductOptionValue::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'product_option_id' => ProductOption::factory(),
            'value' => fake()->word(),
            'position' => 0,
        ];
    }
}
