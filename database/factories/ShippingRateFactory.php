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
            'name' => fake()->randomElement(['Standard', 'Express', 'Economy']),
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => 499],
            'is_active' => true,
        ];
    }

    /**
     * Mark the rate as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Use a weight-based tier configuration.
     */
    public function weightBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ShippingRateType::Weight,
            'config_json' => [
                'ranges' => [
                    ['min_g' => 0, 'max_g' => 500, 'amount' => 399],
                    ['min_g' => 501, 'max_g' => 2000, 'amount' => 699],
                    ['min_g' => 2001, 'max_g' => 1000000, 'amount' => 1299],
                ],
            ],
        ]);
    }

    /**
     * Use a flat rate with an explicit amount.
     */
    public function flatAmount(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => $amount],
        ]);
    }
}
