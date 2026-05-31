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
            'config_json' => ['amount' => 499],
            'is_active' => true,
        ];
    }

    public function flat(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ShippingRateType::Flat->value,
            'config_json' => ['amount' => $amount],
        ]);
    }

    /**
     * @param  list<array{min_g: int, max_g?: int, amount: int}>  $ranges
     */
    public function weight(array $ranges): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ShippingRateType::Weight->value,
            'config_json' => ['ranges' => $ranges],
        ]);
    }

    /**
     * @param  list<array{min_amount: int, max_amount?: int, amount: int}>  $ranges
     */
    public function price(array $ranges): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ShippingRateType::Price->value,
            'config_json' => ['ranges' => $ranges],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
