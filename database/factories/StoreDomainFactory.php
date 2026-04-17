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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'hostname' => fake()->unique()->domainName(),
            'type' => StoreDomainType::Storefront->value,
            'is_primary' => 1,
            'tls_mode' => 'managed',
            'created_at' => now(),
        ];
    }

    public function admin(): self
    {
        return $this->state(fn (): array => ['type' => StoreDomainType::Admin->value]);
    }
}
