<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class CustomerFactory extends Factory
{
    protected $model = \App\Models\Customer::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'first_name' => fake()->firstName(), 'last_name' => fake()->lastName(), 'email' => fake()->unique()->safeEmail(), 'password_hash' => Hash::make('password'), 'status' => 'active', 'email_verified_at' => now(), 'metadata' => ['marketing_opt_in' => fake()->boolean(30)]];
    }

    public function guest(): static
    {
        return $this->state(['password_hash' => null, 'email_verified_at' => null]);
    }

    public function optedInMarketing(): static
    {
        return $this->state(['metadata' => ['marketing_opt_in' => true]]);
    }
}
