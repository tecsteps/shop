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

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'handle' => Str::slug($name.'-'.$this->faker->unique()->numberBetween(1000, 99999)),
            'status' => StoreStatus::Active,
            'default_currency' => 'USD',
            'default_locale' => 'en',
            'timezone' => 'UTC',
        ];
    }

    public function suspended(): static
    {
        return $this->state(['status' => StoreStatus::Suspended]);
    }
}
