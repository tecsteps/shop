<?php

namespace Database\Factories;

use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingZone>
 */
class ShippingZoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->randomElement(['Domestic', 'Europe', 'International']),
            'countries_json' => ['US'],
            'regions_json' => [],
        ];
    }
}
