<?php

namespace Tests;

use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreUser;
use App\Models\User;
use App\Tenancy\CurrentStore;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a store with domain and an owner membership, and bind it
     * as the current store.
     *
     * @param  array<string, mixed>  $storeAttributes
     * @return array{store: Store, domain: StoreDomain, user: User}
     */
    protected function createStoreContext(array $storeAttributes = [], StoreUserRole $role = StoreUserRole::Owner): array
    {
        $store = Store::factory()->create(['status' => StoreStatus::Active, ...$storeAttributes]);
        $domain = StoreDomain::factory()->create(['store_id' => $store->getKey()]);
        $user = User::factory()->create();
        StoreUser::factory()->create([
            'store_id' => $store->getKey(),
            'user_id' => $user->getKey(),
            'role' => $role,
        ]);

        app(CurrentStore::class)->set($store);

        return ['store' => $store, 'domain' => $domain, 'user' => $user];
    }

    protected function actingAsAdmin(User $user, ?Store $store = null): static
    {
        $store ??= $user->stores()->first();

        $this->actingAs($user);

        if ($store !== null) {
            $this->withSession(['current_store_id' => $store->getKey()]);
            app(CurrentStore::class)->set($store);
        }

        return $this;
    }

    protected function actingAsCustomer(Customer $customer): static
    {
        $this->actingAs($customer, 'customer');

        return $this;
    }
}
