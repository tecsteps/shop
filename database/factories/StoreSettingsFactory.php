<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreSettings>
 */
class StoreSettingsFactory extends Factory
{
    protected $model = StoreSettings::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'settings_json' => [
                'contact_email' => fake()->companyEmail(),
            ],
            'updated_at' => now(),
        ];
    }
}
