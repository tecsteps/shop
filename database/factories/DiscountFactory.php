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
            'code' => strtoupper(fake()->unique()->lexify('????-????')),
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 1000,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Active,
        ];
    }

    public function fixed(int $amount = 500): static
    {
        return $this->state(fn (array $attributes): array => [
            'value_type' => DiscountValueType::Fixed,
            'value_amount' => $amount,
        ]);
    }

    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes): array => [
            'value_type' => DiscountValueType::FreeShipping,
            'value_amount' => 0,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => now()->subDays(60),
            'ends_at' => now()->subDay(),
            'status' => DiscountStatus::Expired,
        ]);
    }

    public function maxedOut(): static
    {
        return $this->state(fn (array $attributes): array => [
            'usage_limit' => 5,
            'usage_count' => 5,
        ]);
    }

    public function automatic(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => DiscountType::Automatic,
            'code' => null,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DiscountStatus::Draft,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DiscountStatus::Disabled,
            'starts_at' => now()->subDays(30),
        ]);
    }
}
