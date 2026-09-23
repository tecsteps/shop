<?php

namespace Database\Factories;

use App\Models\ProductVariant;
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
            'variant_id' => ProductVariant::factory(),
            'store_id' => fn (array $attributes): int => (int) ProductVariant::query()->with('product')->findOrFail($attributes['variant_id'])->product->store_id,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
            'policy' => 'deny',
        ];
    }
}
