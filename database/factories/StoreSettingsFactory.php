<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StoreSettings> */
class StoreSettingsFactory extends Factory
{
    protected $model = StoreSettings::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'store_name' => fake()->company(),
            'store_email' => fake()->companyEmail(),
            'timezone' => 'UTC',
            'weight_unit' => 'kg',
            'currency' => 'USD',
            'checkout_requires_account' => false,
        ];
    }
}
