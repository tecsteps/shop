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
            TaxSettings::query()->create([
                'store_id' => $store->id,
                'mode' => 'manual',
                'provider' => 'none',
                'prices_include_tax' => true,
                'config_json' => [
                    'default_rate' => 1900,
                    'tax_name' => 'VAT',
                ],
            ]);
        }
    }
}
