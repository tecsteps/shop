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
            'code' => strtoupper(fake()->unique()->lexify('????##')),
            'title' => fake()->words(3, true),
            'type' => DiscountType::Code,
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
            'status' => DiscountStatus::Active,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'usage_limit' => null,
            'usage_count' => 0,
            'minimum_purchase_amount' => null,
            'rules_json' => null,
            'metadata' => null,
        ];
    }

    public function fixed(int $amountCents = 500): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => DiscountValueType::Fixed,
            'value_amount' => $amountCents,
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

    public function notYetActive(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->addDay(),
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DiscountStatus::Disabled,
        ]);
    }
}
