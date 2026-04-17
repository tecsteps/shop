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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'zone_id' => ShippingZone::factory(),
            'name' => 'Standard',
            'type' => ShippingRateType::Flat->value,
            'config_json' => ['amount' => 799],
            'is_active' => 1,
        ];
    }

    public function weight(): self
    {
        return $this->state(fn (): array => [
            'type' => ShippingRateType::Weight->value,
            'config_json' => ['ranges' => [
                ['min_g' => 0, 'max_g' => 1000, 'amount' => 499],
                ['min_g' => 1001, 'max_g' => 5000, 'amount' => 999],
            ]],
        ]);
    }
}
