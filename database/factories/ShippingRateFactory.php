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
}
