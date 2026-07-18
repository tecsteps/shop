<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $organization = Organization::query()
                ->where('billing_email', 'billing@acme.test')
                ->firstOrFail();

            foreach ([
                ['name' => 'Acme Fashion', 'handle' => 'acme-fashion'],
                ['name' => 'Acme Electronics', 'handle' => 'acme-electronics'],
            ] as $store) {
                Store::query()->updateOrCreate(
                    ['handle' => $store['handle']],
                    [
                        'organization_id' => $organization->id,
                        'name' => $store['name'],
                        'status' => 'active',
                        'default_currency' => 'EUR',
                        'default_locale' => 'en',
                        'timezone' => 'Europe/Berlin',
                    ],
                );
            }
        });
    }
}
