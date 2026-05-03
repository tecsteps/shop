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
            'order_id' => Order::factory()->paid(),
            'status' => FulfillmentShipmentStatus::Pending,
            'tracking_company' => fake()->optional()->randomElement(['DHL', 'UPS', 'FedEx']),
            'tracking_number' => fake()->optional()->bothify('TRK########'),
            'tracking_url' => null,
            'shipped_at' => null,
            'delivered_at' => null,
        ];
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FulfillmentShipmentStatus::Delivered,
            'shipped_at' => now(),
            'delivered_at' => now(),
        ]);
    }
}
