<?php

namespace Database\Factories;

use App\Enums\TaxMode;
use App\Enums\TaxProvider;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxSettings>
 */
class TaxSettingsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'mode' => TaxMode::Manual,
            'provider' => TaxProvider::None,
            'prices_include_tax' => false,
            'config_json' => ['default_rate' => 0, 'shipping_taxable' => false],
        ];
    }
}
