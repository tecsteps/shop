<?php

namespace Database\Factories;

use App\Enums\InventoryPolicy;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'variant_id' => ProductVariant::factory(),
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::Deny->value,
        ];
    }

    /**
     * Indicate that overselling is allowed.
     */
    public function continue(): static
    {
        return $this->state(fn (array $attributes) => [
            'policy' => InventoryPolicy::Continue->value,
        ]);
    }
}
