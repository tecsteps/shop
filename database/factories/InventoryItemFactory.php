<?php

namespace Database\Factories;

use App\Enums\InventoryPolicy;
use App\Models\Product;
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
        $store = Store::factory()->create();
        $product = Product::factory()->for($store)->create();
        $variant = ProductVariant::withoutEvents(fn () => ProductVariant::factory()->for($product)->create());

        return [
            'store_id' => $store->id,
            'variant_id' => $variant->id,
            'quantity_on_hand' => fake()->numberBetween(0, 100),
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::Deny,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
        ]);
    }

    public function continuePolicy(): static
    {
        return $this->state(fn (array $attributes): array => [
            'policy' => InventoryPolicy::Continue,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'quantity_on_hand' => fake()->numberBetween(1, 3),
        ]);
    }
}
