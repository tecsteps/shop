<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Seeder;

class TaxSettingsSeeder extends Seeder
{
    /**
     * Seed manual 19% tax-inclusive settings for both demo stores (spec 07 section 3.7).
     */
    public function run(): void
    {
        foreach (['acme-fashion', 'acme-electronics'] as $handle) {
            $store = Store::query()->where('handle', $handle)->firstOrFail();

            TaxSettings::query()->updateOrCreate(
                ['store_id' => $store->getKey()],
                [
                    'mode' => 'manual',
                    'provider' => 'none',
                    'prices_include_tax' => true,
                    'config_json' => ['default_rate_bps' => 1900],
                ],
            );
        }
    }
}
