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

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function weightBased(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ShippingRateType::Weight,
            'config_json' => [
                'ranges' => [
                    ['min_g' => 0, 'max_g' => 500, 'amount' => 399],
                    ['min_g' => 501, 'max_g' => 2000, 'amount' => 699],
                    ['min_g' => 2001, 'max_g' => null, 'amount' => 1299],
                ],
            ],
        ]);
    }
}
