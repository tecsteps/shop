<?php

namespace Database\Factories;

use App\Models\ShippingRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShippingRate>
 */
class ShippingRateFactory extends Factory
{
    protected $model = ShippingRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipping_zone_id' => ShippingZoneFactory::new(),
            'name' => fake()->randomElement(['Standard', 'Express', 'Economy']),
            'type' => 'flat',
            'price_amount' => 499,
            'currency' => 'EUR',
            'config_json' => ['amount' => 499],
            'is_active' => true,
            'estimated_days_min' => 3,
            'estimated_days_max' => 5,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function weightBased(): static
    {
        return $this->state(['type' => 'weight', 'config_json' => ['ranges' => [['min_g' => 0, 'max_g' => 500, 'amount' => 399], ['min_g' => 501, 'max_g' => 2000, 'amount' => 699], ['min_g' => 2001, 'max_g' => null, 'amount' => 1299]]]]);
    }
}
