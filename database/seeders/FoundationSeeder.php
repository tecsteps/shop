<?php

namespace Database\Seeders;

use App\Enums\StoreDomainTlsMode;
use App\Enums\StoreDomainType;
use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->updateOrCreate(
            ['billing_email' => 'billing@acme.test'],
            ['name' => 'Acme Commerce GmbH'],
        );

        $store = Store::query()->updateOrCreate(
            ['handle' => 'demo-store'],
            [
                'organization_id' => $organization->id,
                'name' => 'Demo Store',
                'status' => StoreStatus::Active,
                'default_currency' => 'EUR',
                'default_locale' => 'en',
                'timezone' => 'Europe/Berlin',
            ],
        );

        StoreDomain::query()->updateOrCreate(
            ['hostname' => 'demo-store.test'],
            [
                'store_id' => $store->id,
                'type' => StoreDomainType::Storefront,
                'is_primary' => true,
                'tls_mode' => StoreDomainTlsMode::Managed,
                'created_at' => now(),
            ],
        );

        StoreDomain::query()->updateOrCreate(
            ['hostname' => 'admin.demo-store.test'],
            [
                'store_id' => $store->id,
                'type' => StoreDomainType::Admin,
                'is_primary' => false,
                'tls_mode' => StoreDomainTlsMode::Managed,
                'created_at' => now(),
            ],
        );

        StoreDomain::query()->updateOrCreate(
            ['hostname' => 'api.demo-store.test'],
            [
                'store_id' => $store->id,
                'type' => StoreDomainType::Api,
                'is_primary' => false,
                'tls_mode' => StoreDomainTlsMode::BringYourOwn,
                'created_at' => now(),
            ],
        );

        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@demo-store.test'],
            [
                'name' => 'Store Owner',
                'password_hash' => Hash::make('password'),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'last_login_at' => now()->subDay(),
            ],
        );

        $staff = User::query()->updateOrCreate(
            ['email' => 'staff@demo-store.test'],
            [
                'name' => 'Store Staff',
                'password_hash' => Hash::make('password'),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'last_login_at' => now()->subHours(4),
            ],
        );

        StoreUser::query()->updateOrCreate(
            ['store_id' => $store->id, 'user_id' => $owner->id],
            ['role' => StoreUserRole::Owner, 'created_at' => now()],
        );

        StoreUser::query()->updateOrCreate(
            ['store_id' => $store->id, 'user_id' => $staff->id],
            ['role' => StoreUserRole::Staff, 'created_at' => now()],
        );

        StoreSettings::query()->updateOrCreate(
            ['store_id' => $store->id],
            [
                'settings_json' => [
                    'branding' => [
                        'store_name' => $store->name,
                        'currency' => $store->default_currency,
                    ],
                    'contact' => [
                        'email' => 'hello@demo-store.test',
                    ],
                ],
                'updated_at' => now(),
            ],
        );
    }
}
