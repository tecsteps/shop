<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Database\Seeder;

class StoreSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('slug', 'acme-fashion')->firstOrFail();

        StoreSettings::create([
            'store_id' => $store->id,
            'store_name' => 'Acme Fashion',
            'store_email' => 'hello@acme.test',
            'timezone' => 'UTC',
            'weight_unit' => 'kg',
            'currency' => 'USD',
        ]);
    }
}
