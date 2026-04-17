<?php

namespace Database\Factories;

use App\Enums\OrderFinancialStatus;
use App\Enums\OrderFulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => Customer::factory(),
            'order_number' => fake()->unique()->bothify('######'),
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Pending,
            'financial_status' => OrderFinancialStatus::Pending,
            'fulfillment_status' => OrderFulfillmentStatus::Unfulfilled,
            'currency' => 'EUR',
            'subtotal_amount' => 1999,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 380,
            'total_amount' => 2878,
            'email' => fake()->safeEmail(),
            'billing_address_json' => [],
            'shipping_address_json' => [],
            'placed_at' => now(),
        ];
    }
}
