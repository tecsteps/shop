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

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'hostname' => fake()->unique()->domainName(),
            'type' => StoreDomainType::Storefront->value,
            'is_primary' => true,
            'tls_mode' => 'managed',
            'created_at' => now(),
        ];
    }
}
