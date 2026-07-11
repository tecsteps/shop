<?php

namespace Database\Factories;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StoreUser>
 */
class StoreUserFactory extends Factory
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
            'user_id' => User::factory(),
            'role' => StoreUserRole::Staff,
            'created_at' => now(),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (): array => [
            'role' => StoreUserRole::Owner,
        ]);
    }
}
