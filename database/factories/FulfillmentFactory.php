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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => FulfillmentShipmentStatus::Pending->value,
            'tracking_company' => null,
            'tracking_number' => null,
            'tracking_url' => null,
            'shipped_at' => null,
            'delivered_at' => null,
        ];
    }

    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentShipmentStatus::Shipped->value,
            'tracking_company' => 'DHL',
            'tracking_number' => fake()->numerify('##########'),
            'shipped_at' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentShipmentStatus::Delivered->value,
            'shipped_at' => now()->subDay(),
            'delivered_at' => now(),
        ]);
    }
}
