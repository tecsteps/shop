<?php

namespace Database\Factories;

use App\Enums\TaxMode;
use App\Enums\TaxProviderType;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxSettings>
 */
class TaxSettingsFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'mode' => TaxMode::Manual->value,
            'provider' => TaxProviderType::None->value,
            'prices_include_tax' => 0,
            'config_json' => ['default_rate_bps' => 0],
        ];
    }
}
