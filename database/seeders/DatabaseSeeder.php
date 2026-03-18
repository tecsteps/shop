<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'billing_email' => 'billing@acme.test',
        ]);

        $store = Store::create([
            'organization_id' => $organization->id,
            'name' => 'Acme Fashion',
            'handle' => 'acme-fashion',
            'status' => 'active',
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'UTC',
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'hostname' => 'acme-fashion.test',
            'type' => 'storefront',
            'is_primary' => true,
        ]);

        StoreSettings::create([
            'store_id' => $store->id,
            'settings_json' => [],
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@acme.test',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $admin->stores()->attach($store->id, ['role' => 'owner']);

        Customer::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'email' => 'customer@acme.test',
            'password_hash' => Hash::make('password'),
            'name' => 'John Doe',
            'marketing_opt_in' => false,
        ]);
    }
}
