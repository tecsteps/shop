<?php

namespace Tests;

use App\Enums\StoreDomainType;
use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return array{organization: Organization, store: Store, domain: StoreDomain, owner: User}
     */
    protected function createStoreContext(array $overrides = []): array
    {
        $organization = Organization::factory()->create();

        $store = Store::factory()->create(array_merge([
            'organization_id' => $organization->id,
            'status' => StoreStatus::Active,
        ], $overrides['store'] ?? []));

        $domain = StoreDomain::factory()->create([
            'store_id' => $store->id,
            'hostname' => $overrides['hostname'] ?? ($store->handle.'.test'),
            'type' => StoreDomainType::Storefront,
            'is_primary' => true,
        ]);

        StoreSettings::factory()->create(['store_id' => $store->id]);

        $owner = User::factory()->create();

        DB::table('store_users')->insert([
            'store_id' => $store->id,
            'user_id' => $owner->id,
            'role' => StoreUserRole::Owner->value,
            'created_at' => now(),
        ]);

        app()->instance('current_store', $store->fresh(['settings']));

        return [
            'organization' => $organization,
            'store' => $store->fresh(),
            'domain' => $domain,
            'owner' => $owner,
        ];
    }

    /**
     * Wire an additional storefront domain using the APP_URL host so HTTP/API tests resolve.
     */
    protected function bindStoreToAppHost(Store $store): StoreDomain
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return StoreDomain::factory()->create([
            'store_id' => $store->id,
            'hostname' => $host,
            'type' => StoreDomainType::Storefront,
            'is_primary' => false,
        ]);
    }

    protected function actingAsAdmin(User $user, ?Store $store = null): static
    {
        $this->actingAs($user);
        if ($store) {
            session()->put('current_store_id', $store->id);
        }

        return $this;
    }

    protected function actingAsCustomer(Customer $customer): static
    {
        $this->actingAs($customer, 'customer');

        return $this;
    }
}
