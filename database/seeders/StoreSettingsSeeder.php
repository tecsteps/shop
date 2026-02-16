<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Database\Seeder;

class StoreSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('slug', 'acme-fashion')->firstOrFail();

        StoreSettings::firstOrCreate(
            ['store_id' => $fashion->id],
            [
                'store_name' => 'Acme Fashion',
                'store_email' => 'hello@acme-fashion.test',
                'timezone' => 'Europe/Berlin',
                'weight_unit' => 'g',
                'currency' => 'EUR',
            ]
        );

        $electronics = Store::where('slug', 'acme-electronics')->firstOrFail();

        StoreSettings::firstOrCreate(
            ['store_id' => $electronics->id],
            [
                'store_name' => 'Acme Electronics',
                'store_email' => 'hello@acme-electronics.test',
                'timezone' => 'Europe/Berlin',
                'weight_unit' => 'g',
                'currency' => 'EUR',
            ]
        );
    }
}
