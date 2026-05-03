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
            'config_json' => [
                'amount' => fake()->numberBetween(499, 1299),
                'estimated_days_min' => 3,
                'estimated_days_max' => 5,
            ],
            'is_active' => true,
        ];
    }

    public function express(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Express Shipping',
            'config_json' => [
                'amount' => 1299,
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
            ],
        ]);
    }
}
