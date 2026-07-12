<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Database\Seeder;

class StoreSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'acme-fashion' => ['store_name' => 'Acme Fashion', 'contact_email' => 'hello@acme-fashion.test', 'order_number_prefix' => '#', 'order_number_start' => 1001, 'bank_transfer_cancel_days' => 7],
            'acme-electronics' => ['store_name' => 'Acme Electronics', 'contact_email' => 'hello@acme-electronics.test', 'order_number_prefix' => '#', 'order_number_start' => 5001, 'bank_transfer_cancel_days' => 7],
        ] as $handle => $settings) {
            $store = Store::query()->where('handle', $handle)->firstOrFail();
            StoreSettings::withoutGlobalScopes()->updateOrCreate(['store_id' => $store->id], ['settings_json' => $settings]);
        }
    }
}
