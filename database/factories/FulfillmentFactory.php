<?php

namespace Database\Factories;

use App\Enums\FulfillmentShipmentStatus;
use App\Models\Fulfillment;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Fulfillment> */
class FulfillmentFactory extends Factory
{
    protected $model = Fulfillment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'tracking_company' => null,
            'tracking_number' => null,
            'tracking_url' => null,
            'status' => FulfillmentShipmentStatus::Pending,
        ];
    }
}
