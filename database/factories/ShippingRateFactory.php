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
    protected $model = ShippingRate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'zone_id' => ShippingZone::factory(),
            'name' => 'Standard Shipping',
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => 499],
            'is_active' => true,
        ];
    }

    public function weightBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Weight-based Shipping',
            'type' => ShippingRateType::Weight,
            'config_json' => [
                'ranges' => [
                    ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
                    ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
                    ['min_g' => 2001, 'max_g' => 10000, 'amount' => 1499],
                ],
            ],
        ]);
    }

    public function priceBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Price-based Shipping',
            'type' => ShippingRateType::Price,
            'config_json' => [
                'ranges' => [
                    ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 799],
                    ['min_amount' => 5001, 'max_amount' => 999999, 'amount' => 399],
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
