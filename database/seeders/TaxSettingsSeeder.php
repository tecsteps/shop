<?php

namespace Database\Seeders;

use App\Enums\TaxMode;
use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxSettingsSeeder extends Seeder
{
    /**
     * Configure manual tax with tax-inclusive prices at 19% (spec 07 §3.7).
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $stores = Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get();

            foreach ($stores as $store) {
                TaxSettings::query()->updateOrCreate(
                    ['store_id' => $store->id],
                    [
                        'mode' => TaxMode::Manual,
                        'provider' => 'none',
                        'prices_include_tax' => true,
                        'config_json' => ['default_rate_bps' => 1900],
                    ],
                );
            }
        });
    }
}
