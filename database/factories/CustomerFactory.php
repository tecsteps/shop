<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

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
            'password_hash' => null,
            'name' => fake()->name(),
            'marketing_opt_in' => fake()->boolean(30),
        ];
    }

    public function registered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'password_hash' => Hash::make('password'),
        ]);
    }
}
