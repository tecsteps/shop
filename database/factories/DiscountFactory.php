<?php

namespace Database\Factories;

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
            'store_id' => \App\Models\Store::factory(),
            'code' => strtoupper(fake()->unique()->bothify('SAVE-####')),
            'title' => 'Test discount',
            'type' => 'percentage',
            'value' => 10,
            'minimum_subtotal_amount' => null,
            'usage_limit' => null,
            'usage_count' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
            'rules_json' => [],
        ];
    }
}
