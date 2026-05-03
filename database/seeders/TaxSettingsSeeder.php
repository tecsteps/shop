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
        Store::query()->get()->each(function (Store $store): void {
            TaxSettings::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $store->getKey()],
                [
                    'mode' => 'manual',
                    'provider' => 'none',
                    'prices_include_tax' => false,
                    'config_json' => [
                        'name' => 'VAT',
                        'default_rate_bps' => 1900,
                        'shipping_taxable' => true,
                        'rates' => [
                            ['country' => 'DE', 'rate_bps' => 1900, 'name' => 'VAT'],
                            ['country' => 'AT', 'rate_bps' => 2000, 'name' => 'VAT'],
                            ['country' => 'CH', 'rate_bps' => 770, 'name' => 'VAT'],
                        ],
                    ],
                ],
            );
        });
    }
}
