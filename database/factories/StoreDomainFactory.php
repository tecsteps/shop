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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'hostname' => fake()->unique()->domainName(),
            'type' => StoreDomainType::Storefront,
            'is_primary' => false,
            'tls_mode' => 'managed',
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StoreDomainType::Admin,
        ]);
    }

    public function api(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StoreDomainType::Api,
        ]);
    }
}
