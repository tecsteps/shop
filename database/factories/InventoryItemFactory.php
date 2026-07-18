<?php

namespace Database\Factories;

use App\Enums\InventoryPolicy;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function configure(): static
    {
        return $this->afterMaking(function (InventoryItem $inventoryItem): void {
            if ($inventoryItem->store_id === null) {
                $inventoryItem->store_id = $inventoryItem->variant->product->store_id;
            }
        });
    }

    public function definition(): array
    {
        return [
            'variant_id' => ProductVariant::factory(),
            'quantity_on_hand' => 100,
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::Deny,
        ];
    }
}
