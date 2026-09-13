<?php

namespace Database\Factories;

use App\Enums\StoreDomainType;
use App\Enums\TlsMode;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreDomainFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'hostname' => fake()->unique()->domainName(),
            'type' => fake()->randomElement(StoreDomainType::cases()),
            'is_primary' => false,
            'tls_mode' => fake()->randomElement(TlsMode::cases()),
        ];
    }
}
