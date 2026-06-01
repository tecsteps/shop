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

/**
 * Seeds the canonical demo tenant used across the application and tests.
 *
 * This is the base hook later phases plug into: call {@see self::store()} to get
 * the demo Store, then attach catalog, orders, etc. Re-running is safe
 * (idempotent via firstOrCreate). The demo store's primary storefront host is
 * the local Herd host `shop.test`, so the storefront resolves out of the box in
 * the browser and in later phases. The admin panel is reachable at
 * `shop.test/admin` (admin store context is session-based, not host-based).
 *
 * Demo credentials (admin guard): admin@shop.test / password (store Owner).
 * Demo customer (customer guard): customer@shop.test / password.
 */
class DemoStoreSeeder extends Seeder
{
    public const STORE_HANDLE = 'acme-fashion';

    /** Primary storefront host — the local Herd host. */
    public const STOREFRONT_HOST = 'shop.test';

    /** Admin host (admin context is session-based; this is for routing/seed parity). */
    public const ADMIN_HOST = 'admin.shop.test';

    /** API host. */
    public const API_HOST = 'api.shop.test';

    /** Secondary storefront host kept for tests/fixtures that reference it. */
    public const ALT_STOREFRONT_HOST = 'acme-fashion.test';

    public const OWNER_EMAIL = 'admin@shop.test';

    public const CUSTOMER_EMAIL = 'customer@shop.test';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = $this->store();

        $owner = User::firstOrCreate(
            ['email' => self::OWNER_EMAIL],
            [
                'name' => 'Demo Owner',
                'password_hash' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );

        $store->users()->syncWithoutDetaching([
            $owner->id => ['role' => StoreUserRole::Owner->value],
        ]);

        Customer::withoutGlobalScopes()->firstOrCreate(
            ['store_id' => $store->id, 'email' => self::CUSTOMER_EMAIL],
            [
                'name' => 'Demo Customer',
                'password_hash' => Hash::make('password'),
                'marketing_opt_in' => true,
            ],
        );
    }

    /**
     * Get (creating if needed) the demo store with its organization, domains,
     * and settings.
     */
    public function store(): Store
    {
        $organization = Organization::firstOrCreate(
            ['billing_email' => 'billing@shop.test'],
            ['name' => 'Acme Holdings'],
        );

        $store = Store::firstOrCreate(
            ['handle' => self::STORE_HANDLE],
            [
                'organization_id' => $organization->id,
                'name' => 'Acme Fashion',
                'status' => StoreStatus::Active->value,
                'default_currency' => 'USD',
                'default_locale' => 'en',
                'timezone' => 'UTC',
            ],
        );

        $this->domain($store, self::STOREFRONT_HOST, StoreDomainType::Storefront, isPrimary: true);
        $this->domain($store, self::ALT_STOREFRONT_HOST, StoreDomainType::Storefront, isPrimary: false);
        $this->domain($store, self::ADMIN_HOST, StoreDomainType::Admin, isPrimary: false);
        $this->domain($store, self::API_HOST, StoreDomainType::Api, isPrimary: false);

        StoreSettings::firstOrCreate(
            ['store_id' => $store->id],
            ['settings_json' => []],
        );

        return $store;
    }

    /**
     * Idempotently attach a domain to the store.
     */
    private function domain(Store $store, string $hostname, StoreDomainType $type, bool $isPrimary): void
    {
        StoreDomain::firstOrCreate(
            ['hostname' => $hostname],
            [
                'store_id' => $store->id,
                'type' => $type->value,
                'is_primary' => $isPrimary,
                'tls_mode' => 'managed',
            ],
        );
    }
}
