<?php

namespace Database\Factories;

use App\Enums\InventoryPolicy;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    protected $model = \App\Models\InventoryItem::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'variant_id' => ProductVariantFactory::new(), 'quantity_on_hand' => fake()->numberBetween(0, 100), 'quantity_reserved' => 0, 'policy' => InventoryPolicy::Deny];
    }

    public function outOfStock(): static
    {
        return $this->state(['quantity_on_hand' => 0]);
    }

    public function continuePolicy(): static
    {
        return $this->state(['policy' => InventoryPolicy::Continue]);
    }

    public function lowStock(): static
    {
        return $this->state(['quantity_on_hand' => fake()->numberBetween(1, 3)]);
    }

    public function backorder(): static
    {
        return $this->state(['quantity_on_hand' => 0, 'policy' => InventoryPolicy::Continue]);
    }

    public function soldOut(): static
    {
        return $this->state(['quantity_on_hand' => 0, 'policy' => InventoryPolicy::Deny]);
    }
}
