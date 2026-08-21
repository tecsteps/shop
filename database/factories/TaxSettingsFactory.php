<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxSettings>
 */
class TaxSettingsFactory extends Factory
{
    protected $model = TaxSettings::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'mode' => 'manual',
            'provider' => 'none',
            'prices_include_tax' => true,
            'config_json' => ['default_rate_bps' => 1900],
            'default_rate_basis_points' => 1900,
            'rates_json' => ['DE' => 1900],
            'provider_config_json' => [],
        ];
    }
}
