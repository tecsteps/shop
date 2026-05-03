<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
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
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->default()->create();
        $quantity = fake()->numberBetween(1, 3);
        $unitPrice = $variant->price_amount;

        return [
            'order_id' => Order::factory()->for($product->store),
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'title_snapshot' => $product->title,
            'sku_snapshot' => $variant->sku,
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'total_amount' => $unitPrice * $quantity,
            'tax_lines_json' => [],
            'discount_allocations_json' => [],
        ];
    }
}
