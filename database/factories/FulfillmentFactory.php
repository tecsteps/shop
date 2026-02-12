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
            'tracking_company' => null,
            'tracking_number' => null,
            'tracking_url' => null,
            'shipped_at' => null,
            'delivered_at' => null,
        ];
    }
}
