<?php

namespace Database\Factories;

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
            'type' => 'flat',
            'config_json' => ['amount' => 499],
            'is_active' => true,
        ];
    }

    public function weightBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Weight-Based Shipping',
            'type' => 'weight',
            'config_json' => [
                'ranges' => [
                    ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
                    ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
                ],
            ],
        ]);
    }

    public function priceBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Price-Based Shipping',
            'type' => 'price',
            'config_json' => [
                'ranges' => [
                    ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 799],
                    ['min_amount' => 5001, 'amount' => 0],
                ],
            ],
        ]);
    }
}
