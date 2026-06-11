<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StoreDomain>
 */
class StoreDomainFactory extends Factory
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
            'hostname' => fake()->unique()->domainName(),
            'type' => 'storefront',
            'is_primary' => true,
            'tls_mode' => 'managed',
        ];
    }

    /**
     * Indicate that the domain serves the admin panel.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'admin',
        ]);
    }

    /**
     * Indicate that the domain serves the API.
     */
    public function api(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'api',
        ]);
    }

    /**
     * Indicate that the domain is not the store's primary domain.
     */
    public function secondary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => false,
        ]);
    }
}
