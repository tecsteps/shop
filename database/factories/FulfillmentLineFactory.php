<?php

namespace Database\Factories;

use App\Models\FulfillmentLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FulfillmentLine>
 */
class FulfillmentLineFactory extends Factory
{
    protected $model = FulfillmentLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fulfillment_id' => FulfillmentFactory::new(),
            'order_line_id' => OrderLineFactory::new(),
            'quantity' => fake()->numberBetween(1, 3),
        ];
    }
}
