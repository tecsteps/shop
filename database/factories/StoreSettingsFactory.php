<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StoreSettings>
 */
class StoreSettingsFactory extends Factory
{
    protected $model = StoreSettings::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'settings_json' => [
                'storefront' => [
                    'contact_email' => fake()->safeEmail(),
                ],
            ],
            'updated_at' => now(),
        ];
    }
}
