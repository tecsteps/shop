<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreDomainFactory extends Factory
{
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

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'admin']);
    }

    public function api(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'api']);
    }

    public function secondary(): static
    {
        return $this->state(fn (array $attributes) => ['is_primary' => false]);
    }
}
