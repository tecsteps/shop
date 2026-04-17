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
            'status' => FulfillmentShipmentStatus::Pending,
        ];
    }

    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentShipmentStatus::Shipped,
            'tracking_company' => 'DHL',
            'tracking_number' => fake()->bothify('##########'),
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
