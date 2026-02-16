<?php

namespace Database\Factories;

use App\Enums\TaxMode;
use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaxSettings> */
class TaxSettingsFactory extends Factory
{
    protected $model = TaxSettings::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'mode' => TaxMode::Manual,
            'rate_basis_points' => 1900,
            'tax_name' => 'Tax',
            'prices_include_tax' => false,
            'charge_tax_on_shipping' => false,
        ];
    }
}
