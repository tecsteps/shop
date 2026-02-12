<?php

namespace Database\Factories;

use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShippingZone> */
class ShippingZoneFactory extends Factory
{
    protected $model = ShippingZone::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->country(),
            'countries_json' => ['DE'],
            'regions_json' => [],
        ];
    }
}
