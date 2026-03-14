<?php

namespace Database\Factories;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => DiscountType::Code,
            'code' => strtoupper(fake()->unique()->lexify('??????')),
            'value_type' => DiscountValueType::Percent,
            'value_amount' => fake()->numberBetween(5, 50),
            'starts_at' => now(),
            'ends_at' => null,
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Active,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DiscountStatus::Expired,
            'ends_at' => now()->subDay(),
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DiscountStatus::Disabled,
        ]);
    }

    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => DiscountValueType::FreeShipping,
            'value_amount' => 0,
        ]);
    }

    public function fixed(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => DiscountValueType::Fixed,
            'value_amount' => $amount,
        ]);
    }
}
