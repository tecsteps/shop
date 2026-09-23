<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShippingRate>
 */
class ShippingRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipping_zone_id' => \App\Models\ShippingZone::factory(),
            'name' => 'Standard shipping',
            'type' => 'flat',
            'price_amount' => 500,
            'min_order_amount' => null,
            'max_order_amount' => null,
            'config_json' => ['estimated_days_min' => 3, 'estimated_days_max' => 5],
            'is_active' => true,
        ];
    }
}
