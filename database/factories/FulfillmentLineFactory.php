<?php

namespace Database\Factories;

use App\Models\Fulfillment;
use App\Models\OrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class FulfillmentLineFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'fulfillment_id' => Fulfillment::factory(),
            'order_line_id' => OrderLine::factory(),
            'quantity' => fake()->numberBetween(1, 5),
        ];
    }
}
