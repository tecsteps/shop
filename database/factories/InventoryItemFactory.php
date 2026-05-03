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
            'variant_id' => fn (): int => ProductVariant::withoutEvents(
                fn (): int => ProductVariant::factory()->create()->getKey(),
            ),
            'store_id' => fn (array $attributes): mixed => ProductVariant::query()
                ->with('product')
                ->find($attributes['variant_id'])
                ?->product
                ?->store_id ?? Store::factory(),
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
            'quantity_reserved' => 0,
        ]);
    }
}
