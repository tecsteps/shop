<?php

namespace Database\Factories;

use App\Enums\ShippingRateType;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingRate>
 */
class ShippingRateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'zone_id' => ShippingZone::factory(),
            'name' => 'Standard Shipping',
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => 500],
            'is_active' => true,
        ];
    }

    public function weightBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ShippingRateType::Weight,
            'config_json' => [
                'ranges' => [
                    ['min_g' => 0, 'max_g' => 1000, 'amount' => 500],
                    ['min_g' => 1001, 'max_g' => 5000, 'amount' => 1000],
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
                    ['min_amount' => 5001, 'amount' => 0],
                ],
            ],
        ]);
    }
}
