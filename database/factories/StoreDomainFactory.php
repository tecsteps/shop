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
    protected $model = StoreDomain::class;

    /**
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
            'is_primary' => false,
        ]);
    }

    public function api(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => StoreDomainType::Api,
            'is_primary' => false,
        ]);
    }

    public function secondary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_primary' => false,
        ]);
    }
}
