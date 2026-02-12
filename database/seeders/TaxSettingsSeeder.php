<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Seeder;

class TaxSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

        foreach ([$fashion, $electronics] as $store) {
            TaxSettings::updateOrCreate(
                ['store_id' => $store->id],
                [
                    'mode' => 'manual',
                    'prices_include_tax' => true,
                    'tax_rate_basis_points' => 1900,
                    'tax_name' => 'VAT',
                    'shipping_taxable' => true,
                ],
            );
        }
    }
}
