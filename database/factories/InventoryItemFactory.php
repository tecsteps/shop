<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'variant_id' => ProductVariant::factory(),
            'quantity_on_hand' => fake()->numberBetween(0, 100),
            'quantity_reserved' => 0,
            'policy' => 'deny',
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => ['quantity_on_hand' => 0]);
    }

    public function continuePolicy(): static
    {
        return $this->state(fn (array $attributes) => ['policy' => 'continue']);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => ['quantity_on_hand' => fake()->numberBetween(1, 3)]);
    }
}
