<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Seeder;

class TaxSettingsSeeder extends Seeder
{
    public function run(): void
    {
        Store::query()->each(fn (Store $store) => TaxSettings::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id],
            ['mode' => 'manual', 'provider' => 'none', 'prices_include_tax' => true, 'config_json' => ['default_rate_bps' => 1900, 'label' => 'VAT']],
        ));
    }
}
