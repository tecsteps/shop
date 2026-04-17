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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => FulfillmentShipmentStatus::Pending,
            'tracking_company' => fake()->optional()->company(),
            'tracking_number' => fake()->optional()->bothify('TRACK-########'),
            'tracking_url' => null,
            'shipped_at' => null,
            'created_at' => now(),
        ];
    }

    /**
     * Indicate that the fulfillment has been shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentShipmentStatus::Shipped,
            'shipped_at' => now(),
        ]);
    }

    /**
     * Indicate that the fulfillment has been delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentShipmentStatus::Delivered,
            'shipped_at' => now(),
        ]);
    }
}
