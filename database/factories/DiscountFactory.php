<?php

namespace Database\Factories;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Discount>
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
            'code' => strtoupper(fake()->unique()->bothify('CODE##??')),
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => null,
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Active,
        ];
    }

    /**
     * Indicate a percent discount with the given whole percentage.
     */
    public function percent(int $value = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => DiscountValueType::Percent,
            'value_amount' => $value,
        ]);
    }

    /**
     * Indicate a fixed-amount discount in minor units.
     */
    public function fixed(int $amount = 500): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => DiscountValueType::Fixed,
            'value_amount' => $amount,
        ]);
    }

    /**
     * Indicate a free-shipping discount.
     */
    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => DiscountValueType::FreeShipping,
            'value_amount' => 0,
        ]);
    }

    /**
     * Indicate an automatic (codeless) discount.
     */
    public function automatic(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DiscountType::Automatic,
            'code' => null,
        ]);
    }

    /**
     * Indicate that the discount is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the discount has reached its usage limit.
     */
    public function maxedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => 10,
            'usage_count' => 10,
        ]);
    }

    /**
     * Set a minimum purchase amount rule in minor units.
     */
    public function withMinPurchase(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'rules_json' => array_merge($attributes['rules_json'] ?? [], ['min_purchase_amount' => $amount]),
        ]);
    }
}
