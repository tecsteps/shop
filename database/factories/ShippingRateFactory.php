<?php

namespace Database\Factories;

use App\Enums\ShippingRateType;
use App\Models\ShippingZone;
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
            'zone_id' => ShippingZone::factory(),
            'name' => 'Standard Shipping',
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => 799],
            'is_active' => true,
        ];
    }

    public function weight(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ShippingRateType::Weight,
            'config_json' => [
                'ranges' => [
                    ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
                    ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
                ],
            ],
        ]);
    }

    public function price(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ShippingRateType::Price,
            'config_json' => [
                'ranges' => [
                    ['min_amount' => 0, 'max_amount' => 7500, 'amount' => 799],
                    ['min_amount' => 7501, 'amount' => 0],
                ],
            ],
        ]);
    }
}
