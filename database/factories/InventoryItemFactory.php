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

    public function withStock(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_on_hand' => $quantity,
        ]);
    }

    public function continuePolicy(): static
    {
        return $this->state(fn (array $attributes) => [
            'policy' => InventoryPolicy::Continue,
        ]);
    }
}
