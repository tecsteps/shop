<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxSettingsSeeder extends Seeder
{
    /**
     * Seed tax settings for both stores.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

            foreach ([$fashion, $electronics] as $store) {
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
