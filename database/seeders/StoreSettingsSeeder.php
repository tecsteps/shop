<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSettingsSeeder extends Seeder
{
    /**
     * Insert the per-store JSON settings (spec 07 §3.6).
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $settingsByHandle = [
                'acme-fashion' => [
                    'store_name' => 'Acme Fashion',
                    'contact_email' => 'hello@acme-fashion.test',
                    'order_number_prefix' => '#',
                    'order_number_start' => 1001,
                ],
                'acme-electronics' => [
                    'store_name' => 'Acme Electronics',
                    'contact_email' => 'hello@acme-electronics.test',
                    'order_number_prefix' => '#',
                    'order_number_start' => 5001,
                ],
            ];

            foreach ($settingsByHandle as $handle => $settings) {
                $store = Store::query()->where('handle', $handle)->firstOrFail();

                StoreSettings::query()->updateOrCreate(
                    ['store_id' => $store->id],
                    ['settings_json' => $settings],
                );
            }
        });
    }
}
