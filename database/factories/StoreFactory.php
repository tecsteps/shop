<?php

namespace Database\Factories;

use App\Enums\StoreStatus;
use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company().' Store';

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'handle' => Str::slug(fake()->unique()->words(2, true)),
            'status' => StoreStatus::Active,
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StoreStatus::Suspended,
        ]);
    }
}
