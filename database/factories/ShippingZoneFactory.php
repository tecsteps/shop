<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShippingZone>
 */
class ShippingZoneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->randomElement(['Domestic', 'Europe', 'International']),
            'countries_json' => ['DE', 'AT', 'CH'],
            'regions_json' => [],
        ];
    }

    /**
     * @param  list<string>  $countries
     */
    public function countries(array $countries): static
    {
        return $this->state(fn (array $attributes) => ['countries_json' => $countries]);
    }

    /**
     * @param  list<string>  $regions
     */
    public function regions(array $regions): static
    {
        return $this->state(fn (array $attributes) => ['regions_json' => $regions]);
    }
}
