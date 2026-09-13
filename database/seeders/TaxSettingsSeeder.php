<?php

namespace Database\Seeders;

use App\Enums\TaxMode;
use App\Enums\TaxProvider;
use App\Models\Store;
use App\Models\TaxSetting;
use Illuminate\Database\Seeder;

class TaxSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['acme-fashion', 'acme-electronics'] as $handle) {
            $store = Store::query()->where('handle', $handle)->firstOrFail();

            TaxSetting::query()->updateOrCreate(
                ['store_id' => $store->getKey()],
                [
                    'mode' => TaxMode::Manual,
                    'provider' => TaxProvider::None,
                    'prices_include_tax' => true,
                    'config_json' => [
                        'default_rate_bps' => 1900,
                        'default_rate' => 1900,
                    ],
                ]
            );
        }
    }
}
