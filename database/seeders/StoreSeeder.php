<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSeeder extends Seeder
{
    /**
     * Seed the stores table.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $org = Organization::where('billing_email', 'billing@acme.test')->firstOrFail();

            Store::firstOrCreate(
                ['handle' => 'acme-fashion'],
                [
                    'organization_id' => $org->id,
                    'name' => 'Acme Fashion',
                    'status' => 'active',
                    'default_currency' => 'EUR',
                    'default_locale' => 'en',
                    'timezone' => 'Europe/Berlin',
                ],
            );

            Store::firstOrCreate(
                ['handle' => 'acme-electronics'],
                [
                    'organization_id' => $org->id,
                    'name' => 'Acme Electronics',
                    'status' => 'active',
                    'default_currency' => 'EUR',
                    'default_locale' => 'en',
                    'timezone' => 'Europe/Berlin',
                ],
            );
        });
    }
}
