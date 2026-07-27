<?php

namespace Tests;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create an organization, a store, and its primary storefront domain.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function createStore(array $attributes = []): Store
    {
        $store = Store::factory()->create($attributes);

        StoreDomain::factory()->create([
            'store_id' => $store->id,
            'hostname' => $store->handle.'.test',
            'is_primary' => true,
        ]);

        return $store;
    }

    /**
     * Bind a store as the current tenant in the container.
     */
    protected function bindStore(Store $store): void
    {
        app()->instance('current_store', $store);
    }

    /**
     * Create a user holding the given role for the store.
     */
    protected function createUserWithRole(Store $store, string|StoreUserRole $role): User
    {
        $user = User::factory()->create();

        StoreUser::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role instanceof StoreUserRole ? $role : StoreUserRole::from($role),
        ]);

        return $user;
    }
}
