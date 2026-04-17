<?php

namespace Database\Seeders;

use App\Enums\StoreDomainType;
use App\Enums\StoreUserRole;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first() ?? Organization::factory()->create([
            'name' => 'Acme Corp',
            'billing_email' => 'billing@acme.test',
        ]);

        // Store 1: Acme Fashion
        $store1 = Store::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Acme Fashion',
            'handle' => 'acme-fashion',
            'status' => 'active',
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ]);

        StoreDomain::factory()->create([
            'store_id' => $store1->id,
            'hostname' => 'acme-fashion.test',
            'type' => StoreDomainType::Storefront,
            'is_primary' => true,
        ]);

        StoreDomain::factory()->create([
            'store_id' => $store1->id,
            'hostname' => 'admin.acme-fashion.test',
            'type' => StoreDomainType::Admin,
            'is_primary' => false,
        ]);

        StoreSettings::factory()->create([
            'store_id' => $store1->id,
            'settings_json' => [
                'store_name' => 'Acme Fashion',
                'contact_email' => 'hello@acme-fashion.test',
                'order_number_prefix' => '#',
                'order_number_start' => 1001,
            ],
        ]);

        // Store 2: Acme Electronics
        $store2 = Store::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Acme Electronics',
            'handle' => 'acme-electronics',
            'status' => 'active',
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ]);

        StoreDomain::factory()->create([
            'store_id' => $store2->id,
            'hostname' => 'acme-electronics.test',
            'type' => StoreDomainType::Storefront,
            'is_primary' => true,
        ]);

        StoreSettings::factory()->create([
            'store_id' => $store2->id,
            'settings_json' => [
                'store_name' => 'Acme Electronics',
                'contact_email' => 'hello@acme-electronics.test',
                'order_number_prefix' => '#',
                'order_number_start' => 5001,
            ],
        ]);

        // Users
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@acme.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'last_login_at' => now(),
        ]);

        $staff = User::factory()->create([
            'name' => 'Staff User',
            'email' => 'staff@acme.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'last_login_at' => now()->subDays(2),
        ]);

        $support = User::factory()->create([
            'name' => 'Support User',
            'email' => 'support@acme.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'last_login_at' => now()->subDay(),
        ]);

        $manager = User::factory()->create([
            'name' => 'Store Manager',
            'email' => 'manager@acme.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'last_login_at' => now()->subDay(),
        ]);

        $admin2 = User::factory()->create([
            'name' => 'Admin Two',
            'email' => 'admin2@acme.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'last_login_at' => now()->subDay(),
        ]);

        // StoreUser pivot
        $store1->users()->attach($admin, ['role' => StoreUserRole::Owner]);
        $store1->users()->attach($staff, ['role' => StoreUserRole::Staff]);
        $store1->users()->attach($support, ['role' => StoreUserRole::Support]);
        $store1->users()->attach($manager, ['role' => StoreUserRole::Admin]);
        $store2->users()->attach($admin2, ['role' => StoreUserRole::Owner]);
    }
}
