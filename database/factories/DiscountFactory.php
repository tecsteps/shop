<?php

namespace Database\Factories;

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
            'code' => strtoupper(fake()->unique()->bothify('??##??')),
            'type' => 'code',
            'value_type' => 'percent',
            'value_amount' => 10,
            'status' => 'active',
            'starts_at' => now()->subDay()->toIso8601String(),
            'ends_at' => now()->addMonth()->toIso8601String(),
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'minimum_purchase_amount' => null,
        ];
    }

    public function fixed(int $amount = 500): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => 'fixed',
            'value_amount' => $amount,
        ]);
    }

    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => 'free_shipping',
            'value_amount' => 0,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'starts_at' => now()->subMonth()->toIso8601String(),
            'ends_at' => now()->subDay()->toIso8601String(),
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disabled',
        ]);
    }

    public function automatic(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'automatic',
            'code' => null,
        ]);
    }
}
