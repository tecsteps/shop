<?php

namespace Database\Factories;

use App\Enums\StoreStatus;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->company(),
            'handle' => fake()->unique()->slug(2),
            'status' => StoreStatus::Active,
            'default_currency' => 'USD',
            'default_locale' => 'en',
            'timezone' => 'UTC',
        ];
    }
}
