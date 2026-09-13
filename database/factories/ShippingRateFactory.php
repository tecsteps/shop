<?php

namespace Database\Factories;

use App\Enums\ShippingRateType;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShippingRateFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'zone_id' => ShippingZone::factory(),
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(ShippingRateType::cases()),
            'config_json' => ['amount' => 799],
            'is_active' => true,
        ];
    }
}
