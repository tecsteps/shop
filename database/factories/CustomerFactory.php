<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
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
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'name' => fake()->name(),
            'marketing_opt_in' => false,
        ];
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes): array => [
            'password' => null,
        ]);
    }
}
