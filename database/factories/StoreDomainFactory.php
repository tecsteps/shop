<?php

namespace Database\Factories;

use App\Enums\StoreDomainType;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreDomain>
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
            'type' => StoreDomainType::Storefront,
            'is_primary' => true,
            'tls_mode' => 'managed',
        ];
    }

    /**
     * Indicate that this is an admin domain.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StoreDomainType::Admin,
        ]);
    }

    /**
     * Indicate that this is an API domain.
     */
    public function api(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StoreDomainType::Api,
        ]);
    }
}
