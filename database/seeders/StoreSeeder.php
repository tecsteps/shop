<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSeeder extends Seeder
{
    /**
     * Create the two demo stores owned by "Acme Corp".
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $organization = Organization::where('name', 'Acme Corp')->firstOrFail();

            $stores = [
                [
                    'handle' => 'acme-fashion',
                    'name' => 'Acme Fashion',
                    'status' => 'active',
                    'default_currency' => 'EUR',
                    'default_locale' => 'en',
                    'timezone' => 'Europe/Berlin',
                ],
                [
                    'handle' => 'acme-electronics',
                    'name' => 'Acme Electronics',
                    'status' => 'active',
                    'default_currency' => 'EUR',
                    'default_locale' => 'en',
                    'timezone' => 'Europe/Berlin',
                ],
            ];

            foreach ($stores as $store) {
                Store::updateOrCreate(
                    ['handle' => $store['handle']],
                    [
                        'organization_id' => $organization->id,
                        'name' => $store['name'],
                        'status' => $store['status'],
                        'default_currency' => $store['default_currency'],
                        'default_locale' => $store['default_locale'],
                        'timezone' => $store['timezone'],
                    ],
                );
            }
        });
    }
}
