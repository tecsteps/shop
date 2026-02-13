<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $organization = Organization::create([
            'name' => 'Default Organization',
            'billing_email' => 'billing@shop.test',
        ]);

        $store = Store::create([
            'organization_id' => $organization->id,
            'name' => 'Default Store',
            'handle' => 'default-store',
            'status' => 'active',
            'default_currency' => 'USD',
            'default_locale' => 'en',
            'timezone' => 'UTC',
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'hostname' => 'shop.test',
            'type' => 'storefront',
            'is_primary' => true,
            'tls_mode' => 'managed',
        ]);

        StoreSettings::create([
            'store_id' => $store->id,
            'settings_json' => [],
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@shop.test',
            'status' => 'active',
        ]);

        $store->users()->attach($admin->id, ['role' => 'owner']);
    }
}
