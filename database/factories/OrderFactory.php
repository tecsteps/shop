<?php

namespace Database\Factories;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'checkout_id' => null,
            'customer_id' => null,
            'order_number' => '#'.fake()->unique()->numberBetween(1001, 999999),
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
            'fulfillment_status' => FulfillmentOrderStatus::Unfulfilled,
            'currency' => 'USD',
            'subtotal_amount' => 0,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'email' => fake()->safeEmail(),
            'billing_address_json' => null,
            'shipping_address_json' => null,
            'placed_at' => now(),
        ];
    }

    /**
     * Indicate that the order is paid (instant capture).
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
        ]);
    }

    /**
     * Indicate that the order awaits a bank transfer payment.
     */
    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
        ]);
    }

    /**
     * Indicate that the order is fulfilled.
     */
    public function fulfilled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Fulfilled,
            'fulfillment_status' => FulfillmentOrderStatus::Fulfilled,
        ]);
    }

    /**
     * Create order lines with the given quantities.
     *
     * @param  array<int, array{quantity?: int, unit_price_amount?: int, variant_id?: int|null, product_id?: int|null}>  $lines
     */
    public function withLines(array $lines): static
    {
        return $this->afterCreating(function (Order $order) use ($lines): void {
            $subtotal = 0;

            foreach ($lines as $line) {
                $quantity = (int) ($line['quantity'] ?? 1);
                $unitPrice = (int) ($line['unit_price_amount'] ?? 1000);
                $total = $quantity * $unitPrice;
                $subtotal += $total;

                $order->lines()->create([
                    'product_id' => $line['product_id'] ?? null,
                    'variant_id' => $line['variant_id'] ?? null,
                    'title_snapshot' => $line['title_snapshot'] ?? fake()->words(2, true),
                    'sku_snapshot' => $line['sku_snapshot'] ?? fake()->bothify('SKU-####'),
                    'quantity' => $quantity,
                    'unit_price_amount' => $unitPrice,
                    'total_amount' => $total,
                    'tax_lines_json' => $line['tax_lines_json'] ?? [],
                    'discount_allocations_json' => $line['discount_allocations_json'] ?? [],
                ]);
            }

            $order->forceFill([
                'subtotal_amount' => $subtotal,
                'total_amount' => $subtotal + $order->tax_amount + $order->shipping_amount - $order->discount_amount,
            ])->save();
        });
    }
}
