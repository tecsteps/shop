<?php

namespace Database\Factories;

use App\Enums\StoreDomainType;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StoreDomain> */
class StoreDomainFactory extends Factory
{
    protected $model = StoreDomain::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'domain' => fake()->unique()->domainName(),
            'type' => StoreDomainType::Storefront,
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(['is_primary' => true]);
    }
}
