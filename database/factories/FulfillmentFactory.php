<?php

namespace Database\Factories;

use App\Enums\FulfillmentShipmentStatus;
use App\Models\Fulfillment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Fulfillment>
 */
class FulfillmentFactory extends Factory
{
    protected $model = Fulfillment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => OrderFactory::new(),
            'status' => FulfillmentShipmentStatus::Shipped,
            'tracking_company' => fake()->randomElement(['DHL', 'UPS', 'FedEx', 'DPD']),
            'tracking_number' => strtoupper(fake()->bothify('??########')),
            'tracking_url' => null,
            'shipped_at' => now(),
            'delivered_at' => null,
            'fulfilled_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Fulfillment $fulfillment): void {
            if ($fulfillment->tracking_number !== null) {
                $fulfillment->tracking_url = 'https://tracking.example.com/'.$fulfillment->tracking_number;
            }
        });
    }

    public function pending(): static
    {
        return $this->state(['status' => FulfillmentShipmentStatus::Pending, 'tracking_number' => null, 'tracking_url' => null, 'shipped_at' => null]);
    }

    public function delivered(): static
    {
        return $this->state(['status' => FulfillmentShipmentStatus::Delivered, 'delivered_at' => now()]);
    }
}
