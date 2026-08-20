<?php

namespace Database\Factories;

use App\Enums\InventoryPolicy;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    protected $model = \App\Models\InventoryItem::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'variant_id' => ProductVariant::factory(), 'quantity_on_hand' => 50, 'quantity_reserved' => 0, 'policy' => InventoryPolicy::Deny];
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
