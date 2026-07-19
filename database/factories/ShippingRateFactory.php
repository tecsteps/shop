<?php

namespace Database\Factories;

use App\Enums\ShippingRateType;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ShippingRate>
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
            'config_json' => ['amount' => 500],
            'is_active' => true,
        ];
    }

    /**
     * Indicate a flat rate with the given amount.
     */
    public function flat(int $amount = 500): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => $amount],
        ]);
    }

    /**
     * Indicate a weight-based rate with the given ranges.
     *
     * @param  array<int, array{min_g: int, max_g: int, amount: int}>  $ranges
     */
    public function weight(array $ranges = []): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ShippingRateType::Weight,
            'config_json' => [
                'ranges' => $ranges !== [] ? $ranges : [
                    ['min_g' => 0, 'max_g' => 1000, 'amount' => 500],
                    ['min_g' => 1001, 'max_g' => 5000, 'amount' => 1000],
                ],
            ],
        ]);
    }

    /**
     * Indicate a price-based rate with the given ranges.
     *
     * @param  array<int, array{min_amount: int, max_amount?: int, amount: int}>  $ranges
     */
    public function price(array $ranges = []): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ShippingRateType::Price,
            'config_json' => [
                'ranges' => $ranges !== [] ? $ranges : [
                    ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 500],
                    ['min_amount' => 5001, 'amount' => 0],
                ],
            ],
        ]);
    }

    /**
     * Indicate that the rate is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
