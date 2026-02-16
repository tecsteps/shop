<?php

namespace Database\Factories;

use App\Enums\InventoryPolicy;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryItem> */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

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

    public function oversellable(): static
    {
        return $this->state(['policy' => InventoryPolicy::Continue]);
    }

    public function outOfStock(): static
    {
        return $this->state([
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
        ]);
    }
}
