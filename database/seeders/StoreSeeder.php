<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstWhere('billing_email', 'billing@acme.test');

        if (! $organization) {
            return;
        }

        Store::query()->updateOrCreate(
            ['handle' => 'acme-fashion'],
            [
                'organization_id' => $organization->id,
                'name' => 'Acme Fashion',
                'status' => 'active',
                'default_currency' => 'EUR',
                'default_locale' => 'en',
                'timezone' => 'Europe/Berlin',
            ]
        );

        Store::query()->updateOrCreate(
            ['handle' => 'acme-electronics'],
            [
                'organization_id' => $organization->id,
                'name' => 'Acme Electronics',
                'status' => 'active',
                'default_currency' => 'EUR',
                'default_locale' => 'en',
                'timezone' => 'Europe/Berlin',
            ]
        );
    }
}
