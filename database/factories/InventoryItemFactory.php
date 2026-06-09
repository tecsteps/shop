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
            'policy' => InventoryPolicy::Deny,
        ];
    }

    /**
     * Tie the inventory item to an existing variant and its product's store.
     */
    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn (array $attributes) => [
            'variant_id' => $variant->getKey(),
            'store_id' => $variant->product->store_id,
        ]);
    }

    /**
     * Set the on-hand stock level.
     */
    public function withStock(int $quantityOnHand, int $quantityReserved = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_on_hand' => $quantityOnHand,
            'quantity_reserved' => $quantityReserved,
        ]);
    }

    /**
     * Use the "continue" oversell policy.
     */
    public function continueSelling(): static
    {
        return $this->state(fn (array $attributes) => [
            'policy' => InventoryPolicy::Continue,
        ]);
    }
}
