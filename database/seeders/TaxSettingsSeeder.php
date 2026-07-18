<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            Store::query()
                ->whereIn('handle', ['acme-fashion', 'acme-electronics'])
                ->each(function (Store $store): void {
                    TaxSettings::query()->updateOrCreate(
                        ['store_id' => $store->id],
                        [
                            'mode' => 'manual',
                            'provider' => 'none',
                            'prices_include_tax' => true,
                            'config_json' => ['default_rate_bps' => 1900],
                        ],
                    );
                });
        });
    }
}
