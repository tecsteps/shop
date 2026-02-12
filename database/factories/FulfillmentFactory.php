<?php

namespace Database\Factories;

use App\Models\Fulfillment;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Fulfillment> */
class FulfillmentFactory extends Factory
{
    protected $model = Fulfillment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $trackingNumber = strtoupper(fake()->bothify('??########'));

        return [
            'order_id' => Order::factory(),
            'status' => 'shipped',
            'tracking_company' => fake()->randomElement(['DHL', 'UPS', 'FedEx', 'DPD']),
            'tracking_number' => $trackingNumber,
            'tracking_url' => 'https://tracking.example.com/'.$trackingNumber,
            'shipped_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'tracking_number' => null,
            'tracking_url' => null,
            'shipped_at' => null,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }
}
