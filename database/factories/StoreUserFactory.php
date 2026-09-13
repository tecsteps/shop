<?php

namespace Database\Factories;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreUserFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'user_id' => User::factory(),
            'role' => fake()->randomElement(StoreUserRole::cases()),
        ];
    }
}
