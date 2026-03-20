<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Seeder;

class TaxSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::first();

        if (! $store) {
            return;
        }

        TaxSettings::factory()->create([
            'store_id' => $store->id,
            'mode' => 'manual',
            'provider' => 'none',
            'rate' => 1900,
            'prices_include_tax' => false,
            'tax_name' => 'VAT',
            'is_active' => true,
        ]);
    }
}
