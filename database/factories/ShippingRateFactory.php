<?php

namespace Database\Factories;

use App\Enums\ShippingRateType;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShippingRate> */
class ShippingRateFactory extends Factory
{
    protected $model = ShippingRate::class;

    public function definition(): array
    {
        return [
            'zone_id' => ShippingZone::factory(),
            'name' => fake()->randomElement(['Standard', 'Express', 'Overnight']),
            'type' => ShippingRateType::Flat,
            'amount' => fake()->randomElement([500, 999, 1500]),
            'config_json' => null,
            'is_active' => true,
        ];
    }
}
