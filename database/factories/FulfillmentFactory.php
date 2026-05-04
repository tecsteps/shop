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
            'tracking_company' => null,
            'tracking_number' => null,
            'tracking_url' => null,
            'shipped_at' => null,
            'delivered_at' => null,
        ];
    }

    public function withTracking(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tracking_company' => fake()->randomElement(['DHL', 'UPS', 'DPD']),
            'tracking_number' => fake()->bothify('??##########'),
            'tracking_url' => fake()->url(),
        ]);
    }

    public function shipped(): static
    {
        return $this->withTracking()->state(fn (array $attributes): array => [
            'status' => FulfillmentShipmentStatus::Shipped,
            'shipped_at' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->withTracking()->state(fn (array $attributes): array => [
            'status' => FulfillmentShipmentStatus::Delivered,
            'shipped_at' => now()->subDay(),
            'delivered_at' => now(),
        ]);
    }
}
