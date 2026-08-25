<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxSettingsSeeder extends Seeder
{
    /**
     * Configure manual 19% VAT for every demo store.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (Store::all() as $store) {
                TaxSettings::updateOrCreate(
                    ['store_id' => $store->id],
                    [
                        'mode' => 'manual',
                        'provider' => 'none',
                        'prices_include_tax' => true,
                        'config_json' => ['default_rate_bps' => 1900],
                    ],
                );
            }
        });
    }
}
