<?php

namespace Database\Factories;

use App\Enums\StoreDomainType;
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
            'type' => StoreDomainType::Storefront,
            'is_primary' => true,
            'tls_mode' => 'managed',
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => StoreDomainType::Admin,
        ]);
    }

    public function api(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => StoreDomainType::Api,
        ]);
    }

    public function secondary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_primary' => false,
        ]);
    }
}
