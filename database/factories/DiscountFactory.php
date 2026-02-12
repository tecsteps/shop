<?php

namespace Database\Factories;

use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Discount> */
class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'code' => strtoupper(fake()->unique()->bothify('????##')),
            'title' => fake()->words(3, true),
            'type' => 'code',
            'value_type' => 'percent',
            'value_amount' => 10,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addYear(),
            'usage_limit' => null,
            'times_used' => 0,
            'status' => 'active',
        ];
    }

    public function fixed(int $amountCents): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => 'fixed',
            'value_amount' => $amountCents,
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
            'starts_at' => now()->subYear(),
            'ends_at' => now()->subDay(),
            'status' => 'expired',
        ]);
    }

    public function maxedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => 5,
            'times_used' => 5,
        ]);
    }

    public function automatic(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'automatic',
            'code' => null,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disabled',
            'starts_at' => now()->subMonth(),
        ]);
    }
}
