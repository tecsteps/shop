<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\TaxSetting;
use Illuminate\Database\Seeder;

class TaxSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get();

        foreach ($stores as $store) {
            TaxSetting::query()->updateOrCreate(
                ['store_id' => $store->id],
                [
                    'mode' => 'manual',
                    'provider' => 'none',
                    'prices_include_tax' => true,
                    'config_json' => [
                        'default_rate_bps' => 1900,
                    ],
                ]
            );
        }
    }
}
