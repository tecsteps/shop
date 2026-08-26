<?php

namespace Tests\Concerns;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;

trait InteractsWithStore
{
    /**
     * @return array{store: Store, user: User, organization: Organization, domain: StoreDomain}
     */
    public function createStoreContext(): array
    {
        $organization = Organization::factory()->create();
        $store = Store::factory()->create(['organization_id' => $organization->id]);
        $domain = StoreDomain::factory()->create(['store_id' => $store->id]);
        $user = User::factory()->create();
        $user->stores()->attach($store->id, ['role' => 'owner']);

        $this->bindCurrentStore($store);

        return ['store' => $store, 'user' => $user, 'organization' => $organization, 'domain' => $domain];
    }

    public function bindCurrentStore(Store $store): Store
    {
        app()->instance('current_store', $store);

        return $store;
    }

    public function actingAsAdmin(User $user, ?Store $store = null): static
    {
        $this->actingAs($user, 'web');

        if ($store !== null) {
            session(['current_store_id' => $store->id]);
        }

        return $this;
    }

    public function actingAsCustomer(Customer $customer): static
    {
        $this->actingAs($customer, 'customer');

        return $this;
    }
}
