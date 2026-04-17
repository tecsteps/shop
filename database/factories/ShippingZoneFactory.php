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
    protected $model = ShippingZone::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => 'Domestic',
            'countries_json' => ['DE'],
            'regions_json' => [],
            'is_active' => true,
        ];
    }

    public function international(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'International',
            'countries_json' => ['US', 'GB', 'FR'],
        ]);
    }
}
