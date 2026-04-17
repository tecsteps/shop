<?php

namespace Database\Factories;

use App\Enums\InventoryPolicy;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
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
            'quantity_on_hand' => fake()->numberBetween(0, 100),
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::Deny,
        ];
    }

    /**
     * Indicate that overselling is allowed.
     */
    public function allowOverselling(): static
    {
        return $this->state(fn (array $attributes) => [
            'policy' => InventoryPolicy::Continue,
        ]);
    }

    /**
     * Indicate that the item is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
        ]);
    }

    /**
     * Set a specific stock level.
     */
    public function withStock(int $onHand, int $reserved = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_on_hand' => $onHand,
            'quantity_reserved' => $reserved,
        ]);
    }
}
