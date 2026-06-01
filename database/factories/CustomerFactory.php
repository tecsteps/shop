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
     * The current password being used by the factory.
     */
    protected static ?string $password;

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
            'password_hash' => static::$password ??= Hash::make('password'),
            'name' => fake()->name(),
            'marketing_opt_in' => false,
            'remember_token' => null,
        ];
    }

    /**
     * Indicate that the customer is a guest with no password set.
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'password_hash' => null,
        ]);
    }
}
