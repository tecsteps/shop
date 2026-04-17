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

    public function flat(int $amount = 499): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ShippingRateType::Flat->value,
            'config_json' => ['amount' => $amount],
        ]);
    }

    /**
     * @param  array<int, array{min_g: int, max_g: int, amount: int}>  $ranges
     */
    public function weight(array $ranges): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ShippingRateType::Weight->value,
            'config_json' => ['ranges' => $ranges],
        ]);
    }

    /**
     * @param  array<int, array{min_amount: int, max_amount: int, amount: int}>  $ranges
     */
    public function price(array $ranges): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ShippingRateType::Price->value,
            'config_json' => ['ranges' => $ranges],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
