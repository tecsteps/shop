<?php

namespace Database\Factories;

use App\Models\Fulfillment;
use App\Models\OrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FulfillmentLine>
 */
class FulfillmentLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $orderLine = OrderLine::factory()->create();

        return [
            'fulfillment_id' => Fulfillment::factory()->for($orderLine->order),
            'order_line_id' => $orderLine->id,
            'quantity' => $orderLine->quantity,
        ];
    }
}
