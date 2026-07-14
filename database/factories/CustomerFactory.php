<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class CustomerFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => static::$password ??= Hash::make('password'),
            'name' => fake()->name(),
            'marketing_opt_in' => fake()->boolean(30),
        ];
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => ['password_hash' => null]);
    }

    public function optedInMarketing(): static
    {
        return $this->state(fn (array $attributes) => ['marketing_opt_in' => true]);
    }
}
