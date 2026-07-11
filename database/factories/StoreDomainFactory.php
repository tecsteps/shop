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
            'is_primary' => false,
            'tls_mode' => 'managed',
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (): array => [
            'is_primary' => true,
        ]);
    }
}
