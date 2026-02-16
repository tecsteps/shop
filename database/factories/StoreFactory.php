<?php

namespace Database\Factories;

use App\Enums\StoreStatus;
use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Store> */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        $name = fake()->company().' Store';

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'status' => StoreStatus::Active,
            'currency' => 'USD',
        ];
    }

    public function suspended(): static
    {
        return $this->state(['status' => StoreStatus::Suspended]);
    }
}
