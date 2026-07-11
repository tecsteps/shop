<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ([
                'acme-fashion' => ['store_name' => 'Acme Fashion', 'contact_email' => 'hello@acme-fashion.test', 'order_number_prefix' => '#', 'order_number_start' => 1001],
                'acme-electronics' => ['store_name' => 'Acme Electronics', 'contact_email' => 'hello@acme-electronics.test', 'order_number_prefix' => '#', 'order_number_start' => 5001],
            ] as $handle => $settings) {
                $store = Store::query()->where('handle', $handle)->sole();
                StoreSettings::query()->updateOrCreate(['store_id' => $store->id], ['settings_json' => $settings]);
            }
        });
    }
}
