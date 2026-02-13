<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

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

    public function withPassword(string $password = 'password'): static
    {
        return $this->state(fn (array $attributes): array => [
            'password_hash' => Hash::make($password),
        ]);
    }

    public function optedInToMarketing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'marketing_opt_in' => true,
        ]);
    }
}
