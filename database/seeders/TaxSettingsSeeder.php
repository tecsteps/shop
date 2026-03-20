<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Seeder;

class TaxSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();

        foreach ($stores as $store) {
            TaxSettings::factory()->create([
                'store_id' => $store->id,
                'mode' => 'manual',
                'provider' => 'none',
                'rate' => 1900,
                'prices_include_tax' => true,
                'tax_name' => 'VAT',
                'is_active' => true,
            ]);
        }
    }
}
