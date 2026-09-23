<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxSetting>
 */
class TaxSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => \App\Models\Store::factory(),
            'prices_include_tax' => false,
            'default_rate' => 1900,
            'rates_json' => ['DE' => 1900],
        ];
    }
}
