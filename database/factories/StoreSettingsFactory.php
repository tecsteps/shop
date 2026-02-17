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
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'settings_json' => [],
        ];
    }
}
