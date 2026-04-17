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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'variant_id' => ProductVariant::factory(),
            'quantity_on_hand' => fake()->numberBetween(0, 100),
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::Deny->value,
        ];
    }

    public function continuePolicy(): self
    {
        return $this->state(fn (): array => ['policy' => InventoryPolicy::Continue->value]);
    }
}
