<?php

namespace Database\Factories;

use App\Enums\InventoryPolicy;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'variant_id' => ProductVariant::factory(),
            'quantity_on_hand' => fake()->numberBetween(0, 1000),
            'quantity_reserved' => fake()->numberBetween(0, 50),
            'policy' => fake()->randomElement(InventoryPolicy::cases()),
        ];
    }
}
