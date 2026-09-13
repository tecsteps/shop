<?php

namespace Database\Factories;

use App\Enums\FulfillmentShipmentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class FulfillmentFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => fake()->randomElement(FulfillmentShipmentStatus::cases()),
            'tracking_company' => fake()->randomElement(['UPS', 'FedEx', 'DHL', 'USPS']),
            'tracking_number' => fake()->regexify('[A-Z0-9]{12}'),
            'tracking_url' => fake()->url(),
            'shipped_at' => null,
        ];
    }
}
