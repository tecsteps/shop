<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSettingsSeeder extends Seeder
{
    /**
     * Seed per-store settings.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

            StoreSettings::updateOrCreate(
                ['store_id' => $fashion->id],
                [
                    'settings_json' => [
                        'store_name' => 'Acme Fashion',
                        'contact_email' => 'hello@acme-fashion.test',
                        'order_number_prefix' => '#',
                        'order_number_start' => 1001,
                    ],
                ],
            );

            StoreSettings::updateOrCreate(
                ['store_id' => $electronics->id],
                [
                    'settings_json' => [
                        'store_name' => 'Acme Electronics',
                        'contact_email' => 'hello@acme-electronics.test',
                        'order_number_prefix' => '#',
                        'order_number_start' => 5001,
                    ],
                ],
            );
        });
    }
}
