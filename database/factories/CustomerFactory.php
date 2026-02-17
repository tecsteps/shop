<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => null,
            'name' => fake()->name(),
            'marketing_opt_in' => false,
        ];
    }

    public function withPassword(): static
    {
        return $this->state(fn (array $attributes) => [
            'password_hash' => bcrypt('password'),
        ]);
    }
}
