<?php

namespace Database\Seeders;

use App\Enums\TaxMode;
use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Seeder;

class TaxSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('slug', 'acme-fashion')->firstOrFail();

        TaxSettings::firstOrCreate(
            ['store_id' => $fashion->id],
            [
                'mode' => TaxMode::Manual,
                'rate_basis_points' => 1900,
                'tax_name' => 'VAT',
                'prices_include_tax' => true,
                'charge_tax_on_shipping' => true,
            ]
        );

        $electronics = Store::where('slug', 'acme-electronics')->firstOrFail();

        TaxSettings::firstOrCreate(
            ['store_id' => $electronics->id],
            [
                'mode' => TaxMode::Manual,
                'rate_basis_points' => 1900,
                'tax_name' => 'VAT',
                'prices_include_tax' => true,
                'charge_tax_on_shipping' => true,
            ]
        );
    }
}
