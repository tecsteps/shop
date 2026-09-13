<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShippingZoneFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->words(2, true),
            'countries_json' => ['US', 'CA'],
            'regions_json' => [],
        ];
    }
}
