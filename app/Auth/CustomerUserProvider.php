<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Eloquent user provider that scopes every customer lookup to the current store.
 *
 * Storefront customer auth is multi-tenant: the same email may exist in many
 * stores, so credential queries must always filter by `store_id`. The store id
 * is taken from the container-bound `current_store` (set by ResolveStore
 * middleware). The {@see \App\Models\Customer} model also applies the
 * StoreScope global scope, but this provider injects `store_id` explicitly so
 * that retrieval still works even when the scope is bypassed.
 */
class CustomerUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a customer by the given credentials, scoped to the current store.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $storeId = $this->currentStoreId();

        if ($storeId === null) {
            return null;
        }

        $credentials['store_id'] = $storeId;

        return parent::retrieveByCredentials($credentials);
    }

    /**
     * The current store id, or null when no store is resolved.
     */
    protected function currentStoreId(): ?int
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        return app('current_store')->id;
    }
}
