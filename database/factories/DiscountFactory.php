<?php

namespace Database\Factories;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => DiscountType::Code,
            'code' => strtoupper(fake()->unique()->bothify('????##')),
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addYear(),
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Active,
        ];
    }

    /**
     * Use a fixed amount discount in minor units.
     */
    public function fixed(int $amountCents): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => DiscountValueType::Fixed,
            'value_amount' => $amountCents,
        ]);
    }

    /**
     * Use a free shipping discount.
     */
    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => DiscountValueType::FreeShipping,
            'value_amount' => 0,
        ]);
    }

    /**
     * Mark the discount as past its end date.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subYear(),
            'ends_at' => now()->subDay(),
            'status' => DiscountStatus::Expired,
        ]);
    }

    /**
     * Mark the discount as having reached its usage limit.
     */
    public function maxedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => 5,
            'usage_count' => 5,
        ]);
    }

    /**
     * Use an automatic (codeless) discount.
     */
    public function automatic(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DiscountType::Automatic,
            'code' => null,
        ]);
    }

    /**
     * Mark the discount as a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DiscountStatus::Draft,
        ]);
    }

    /**
     * Mark the discount as manually disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DiscountStatus::Disabled,
            'starts_at' => now()->subMonth(),
        ]);
    }
}
