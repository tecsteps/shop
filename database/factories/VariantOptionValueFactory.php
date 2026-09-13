<?php

namespace Database\Factories;

use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class VariantOptionValueFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'variant_id' => ProductVariant::factory(),
            'product_option_value_id' => ProductOptionValue::factory(),
        ];
    }
}
