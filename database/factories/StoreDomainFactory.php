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
            'hostname' => fake()->unique()->domainWord().'.test',
            'type' => StoreDomainType::Storefront->value,
            'is_primary' => true,
            'tls_mode' => 'managed',
        ];
    }

    /**
     * Use a specific hostname.
     */
    public function hostname(string $hostname): static
    {
        return $this->state(fn (array $attributes) => [
            'hostname' => $hostname,
        ]);
    }

    /**
     * Mark the domain as an admin domain.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StoreDomainType::Admin->value,
            'is_primary' => false,
        ]);
    }
}
