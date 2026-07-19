<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<\App\Models\Customer>
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
        ];
    }

    /**
     * Indicate that the customer checked out as a guest (no password).
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'password_hash' => null,
        ]);
    }
}
