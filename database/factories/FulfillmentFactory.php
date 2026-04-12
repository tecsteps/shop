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
    protected $model = Fulfillment::class;

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
        return $this->state(fn (array $attributes): array => [
            'status' => FulfillmentShipmentStatus::Shipped->value,
            'tracking_company' => 'DHL',
            'tracking_number' => fake()->bothify('TRACK########'),
            'tracking_url' => fake()->url(),
            'shipped_at' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FulfillmentShipmentStatus::Delivered->value,
            'tracking_company' => 'DHL',
            'tracking_number' => fake()->bothify('TRACK########'),
            'tracking_url' => fake()->url(),
            'shipped_at' => now()->subDays(2),
            'delivered_at' => now(),
        ]);
    }
}
