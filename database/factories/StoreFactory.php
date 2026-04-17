<?php

namespace Database\Factories;

use App\Enums\StoreStatus;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'handle' => Str::slug($name).'-'.Str::random(4),
            'status' => StoreStatus::Active->value,
            'default_currency' => 'USD',
            'default_locale' => 'en',
            'timezone' => 'UTC',
        ];
    }

    public function suspended(): self
    {
        return $this->state(fn (): array => ['status' => StoreStatus::Suspended->value]);
    }
}
