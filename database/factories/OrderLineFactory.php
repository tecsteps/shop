<?php

namespace Database\Factories;

use App\Models\OrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderLine>
 */
class OrderLineFactory extends Factory
{
    protected $model = OrderLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => OrderFactory::new(),
            'product_id' => ProductFactory::new(),
            'variant_id' => ProductVariantFactory::new(),
            'product_title' => fake()->words(3, true),
            'title_snapshot' => fake()->words(3, true),
            'variant_title' => 'Default',
            'sku' => strtoupper(fake()->bothify('SKU-####-???')),
            'sku_snapshot' => strtoupper(fake()->bothify('SKU-####-???')),
            'quantity' => fake()->numberBetween(1, 3),
            'unit_price_amount' => fake()->numberBetween(999, 19999),
            'line_subtotal_amount' => 0,
            'line_discount_amount' => 0,
            'line_total_amount' => 0,
            'total_amount' => 0,
            'tax_lines_json' => [],
            'discount_allocations_json' => [],
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (OrderLine $line): void {
            $line->line_subtotal_amount = $line->unit_price_amount * $line->quantity;
            $line->line_total_amount = $line->line_subtotal_amount - $line->line_discount_amount;
            $line->total_amount = $line->line_total_amount;
        });
    }
}
