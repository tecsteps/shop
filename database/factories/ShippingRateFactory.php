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
            'name' => fake()->randomElement(['Standard Shipping', 'Express Shipping', 'Economy']),
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => 799],
            'is_active' => true,
        ];
    }

    public function weightBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ShippingRateType::Weight,
            'config_json' => [
                'ranges' => [
                    ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
                    ['min_g' => 501, 'max_g' => 2000, 'amount' => 999],
                ],
            ],
        ]);
    }

    public function priceBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ShippingRateType::Price,
            'config_json' => [
                'ranges' => [
                    ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 799],
                    ['min_amount' => 5001, 'max_amount' => null, 'amount' => 0],
                ],
            ],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
