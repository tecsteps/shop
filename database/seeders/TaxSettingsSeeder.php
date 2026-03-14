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
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        TaxSettings::create([
            'store_id' => $store->id,
            'mode' => TaxMode::Manual,
            'provider' => 'none',
            'prices_include_tax' => false,
            'config_json' => [
                'default_rate' => 1900,
                'default_name' => 'VAT',
            ],
        ]);
    }
}
