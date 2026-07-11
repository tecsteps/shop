<?php

namespace Database\Factories;

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
            'type' => 'code',
            'code' => fake()->unique()->bothify('SAVE##??'),
            'value_type' => 'percent',
            'value_amount' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'usage_count' => 0,
            'rules_json' => [],
            'status' => 'active',
        ];
    }
}
