<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Seeder;

class TaxSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get() as $store) {
            TaxSettings::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $store->getKey()],
                ['mode' => 'manual', 'provider' => 'none', 'prices_include_tax' => true, 'config_json' => ['default_rate_bps' => 1900], 'default_rate_basis_points' => 1900, 'rates_json' => ['DE' => 1900], 'provider_config_json' => []],
            );
        }
    }
}
