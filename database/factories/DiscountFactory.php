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
            'code' => fake()->unique()->bothify('SAVE##'),
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => ['customer_eligibility' => 'all'],
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
}
