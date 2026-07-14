<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class FulfillmentFactory extends Factory
{
    public function definition(): array
    {
        $trackingNumber = fake()->bothify('??########');

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
            'status' => 'pending', 'tracking_number' => null, 'tracking_url' => null, 'shipped_at' => null,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'delivered']);
    }
}
