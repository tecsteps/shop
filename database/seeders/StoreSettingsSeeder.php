<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Database\Seeder;

class StoreSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->firstWhere('handle', 'acme-fashion');
        $electronics = Store::query()->firstWhere('handle', 'acme-electronics');

        if ($fashion) {
            StoreSettings::query()->updateOrCreate(
                ['store_id' => $fashion->id],
                [
                    'settings_json' => [
                        'store_name' => 'Acme Fashion',
                        'contact_email' => 'hello@acme-fashion.test',
                        'order_number_prefix' => '#',
                        'order_number_start' => 1001,
                    ],
                ]
            );
        }

        if ($electronics) {
            StoreSettings::query()->updateOrCreate(
                ['store_id' => $electronics->id],
                [
                    'settings_json' => [
                        'store_name' => 'Acme Electronics',
                        'contact_email' => 'hello@acme-electronics.test',
                        'order_number_prefix' => '#',
                        'order_number_start' => 5001,
                    ],
                ]
            );
        }
    }
}
