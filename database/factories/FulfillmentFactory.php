<?php

namespace Database\Factories;

use App\Enums\FulfillmentShipmentStatus;
use App\Models\Fulfillment;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fulfillment>
 */
class FulfillmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => FulfillmentShipmentStatus::Pending,
            'tracking_company' => null,
            'tracking_number' => null,
            'tracking_url' => null,
            'shipped_at' => null,
            'delivered_at' => null,
            'created_at' => now(),
        ];
    }

    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentShipmentStatus::Shipped,
            'tracking_company' => 'DHL',
            'tracking_number' => fake()->regexify('[A-Z0-9]{12}'),
            'shipped_at' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->shipped()->state(fn (array $attributes) => [
            'status' => FulfillmentShipmentStatus::Delivered,
            'delivered_at' => now(),
        ]);
    }
}
