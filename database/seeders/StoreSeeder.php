<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->where('billing_email', 'billing@acme.test')->firstOrFail();
        foreach ([
            ['name' => 'Acme Fashion', 'handle' => 'acme-fashion'],
            ['name' => 'Acme Electronics', 'handle' => 'acme-electronics'],
        ] as $data) {
            Store::query()->updateOrCreate(['handle' => $data['handle']], [
                'organization_id' => $organization->id,
                'name' => $data['name'],
                'status' => 'active',
                'default_currency' => 'EUR',
                'default_locale' => 'en',
                'timezone' => 'Europe/Berlin',
            ]);
        }
    }
}
