<?php

namespace Database\Seeders;

use App\Enums\TaxMode;
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
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        TaxSettings::query()->updateOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => TaxMode::Manual,
                'provider' => 'none',
                'prices_include_tax' => false,
                'config_json' => [
                    'default_rate_basis_points' => 1900,
                    'shipping_taxable' => true,
                ],
            ],
        );
    }
}
