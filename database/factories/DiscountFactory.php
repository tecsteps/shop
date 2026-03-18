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

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => DiscountType::Code,
            'code' => strtoupper(fake()->unique()->bothify('????##')),
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Active,
        ];
    }

    public function fixed(int $amount = 500): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => DiscountValueType::Fixed,
            'value_amount' => $amount,
        ]);
    }

    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => DiscountValueType::FreeShipping,
            'value_amount' => 0,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'ends_at' => now()->subDay(),
        ]);
    }
}
