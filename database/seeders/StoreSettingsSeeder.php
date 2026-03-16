<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Database\Seeder;

class StoreSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::first();

        StoreSettings::factory()->create([
            'store_id' => $store->id,
            'settings_json' => [
                'store_name' => 'Acme Store',
                'contact_email' => 'support@acme.test',
            ],
        ]);
    }
}
