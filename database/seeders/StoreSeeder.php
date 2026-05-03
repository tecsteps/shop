<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()->where('billing_email', 'billing@acme.test')->firstOrFail();

        Store::query()->updateOrCreate(
            ['handle' => 'acme-fashion'],
            [
                'organization_id' => $organization->getKey(),
                'name' => 'Acme Fashion',
                'status' => 'active',
                'default_currency' => 'EUR',
                'default_locale' => 'en',
                'timezone' => 'Europe/Berlin',
            ],
        );
    }
}
