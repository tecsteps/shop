<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderLine>
 */
class OrderLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'variant_id' => null,
            'title_snapshot' => fake()->words(3, true),
            'sku_snapshot' => strtoupper(fake()->bothify('SKU-####')),
            'quantity' => 1,
            'unit_price_amount' => 2500,
            'total_amount' => 2500,
            'tax_lines_json' => [],
            'discount_allocations_json' => [],
        ];
    }

    /**
     * Reference an existing variant, snapshotting its product and SKU.
     */
    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $variant->product_id,
            'variant_id' => $variant->getKey(),
            'title_snapshot' => $variant->product->title,
            'sku_snapshot' => $variant->sku,
            'unit_price_amount' => $variant->price_amount,
            'total_amount' => $variant->price_amount * ($attributes['quantity'] ?? 1),
        ]);
    }
}
