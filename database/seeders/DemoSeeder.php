<?php

namespace Database\Seeders;

use App\Enums\StoreDomainType;
use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->create([
            'name' => 'Acme Holdings',
            'billing_email' => 'billing@acme.test',
        ]);

        $store = Store::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Acme Fashion',
            'handle' => 'acme-fashion',
            'status' => StoreStatus::Active,
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ]);

        StoreDomain::query()->create([
            'store_id' => $store->id,
            'hostname' => 'shop.test',
            'type' => StoreDomainType::Storefront,
            'is_primary' => true,
            'tls_mode' => 'managed',
            'created_at' => now(),
        ]);

        StoreSettings::query()->create([
            'store_id' => $store->id,
            'settings_json' => [
                'announcement_bar' => 'Free shipping on orders over 50',
                'support_email' => 'support@acme.test',
            ],
            'updated_at' => now(),
        ]);

        $owner = User::query()->create([
            'name' => 'Ada Owner',
            'email' => 'owner@acme.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        \DB::table('store_users')->insert([
            'store_id' => $store->id,
            'user_id' => $owner->id,
            'role' => StoreUserRole::Owner->value,
            'created_at' => now(),
        ]);

        app()->instance('current_store', $store);

        Customer::query()->create([
            'store_id' => $store->id,
            'email' => 'buyer@example.com',
            'password' => Hash::make('password'),
            'first_name' => 'Billy',
            'last_name' => 'Buyer',
            'state' => 'active',
            'email_verified_at' => now(),
        ]);

        app()->forgetInstance('current_store');
    }
}
