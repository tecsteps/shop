<?php

namespace Database\Seeders;

use App\Enums\StoreStatus;
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
                'organization_id' => $organization->id,
                'name' => 'Acme Fashion',
                'status' => StoreStatus::Active,
                'default_currency' => 'EUR',
                'default_locale' => 'en',
                'timezone' => 'Europe/Berlin',
            ],
        );
    }
}
