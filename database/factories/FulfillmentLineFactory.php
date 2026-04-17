<?php

namespace Database\Factories;

use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\OrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FulfillmentLine>
 */
class FulfillmentLineFactory extends Factory
{
    protected $model = FulfillmentLine::class;

    /**
     * @return array<string, mixed>
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
