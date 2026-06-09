<?php

namespace Database\Factories;

use App\Enums\FulfillmentShipmentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Fulfillment>
 */
class FulfillmentFactory extends Factory
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
            'status' => FulfillmentShipmentStatus::Pending,
        ];
    }

    /**
     * A shipped fulfillment with tracking data.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentShipmentStatus::Shipped,
            'tracking_company' => 'DHL',
            'tracking_number' => strtoupper(fake()->bothify('DHL##########')),
            'shipped_at' => now()->subDay(),
        ]);
    }

    /**
     * A delivered fulfillment.
     */
    public function delivered(): static
    {
        return $this->shipped()->state(fn (array $attributes) => [
            'status' => FulfillmentShipmentStatus::Delivered,
            'delivered_at' => now(),
        ]);
    }
}
