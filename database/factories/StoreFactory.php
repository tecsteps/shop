<?php

namespace Database\Factories;

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

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'handle' => Str::slug($name),
            'status' => 'active',
            'default_currency' => 'USD',
            'default_locale' => 'en',
            'timezone' => 'UTC',
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
