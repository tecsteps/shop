<?php

namespace Database\Factories;

use App\Enums\StoreStatus;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
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
        $name = fake()->unique()->company();

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'handle' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'status' => StoreStatus::Active->value,
            'default_currency' => 'USD',
            'default_locale' => 'en',
            'timezone' => 'UTC',
        ];
    }

    /**
     * Indicate that the store is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StoreStatus::Suspended->value,
        ]);
    }
}
