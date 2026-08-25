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

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => 'code',
            'code' => fake()->unique()->bothify('SAVE####'),
            'value_type' => 'percent',
            'value_amount' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => null,
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => 'active',
        ];
    }

    public function fixed(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => 'fixed',
            'value_amount' => $amount,
        ]);
    }

    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes) => ['value_type' => 'free_shipping']);
    }
}
