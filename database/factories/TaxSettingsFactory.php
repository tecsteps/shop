<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaxSettings> */
class TaxSettingsFactory extends Factory
{
    protected $model = TaxSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'mode' => 'manual',
            'prices_include_tax' => true,
            'tax_rate_basis_points' => 1900,
            'tax_name' => 'VAT',
            'shipping_taxable' => true,
        ];
    }
}
