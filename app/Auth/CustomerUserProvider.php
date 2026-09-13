<?php

namespace App\Auth;

use App\Tenancy\CurrentStore;
use Illuminate\Auth\EloquentUserProvider;

/**
 * Store-scoped user provider for the `customer` guard.
 *
 * Injects the resolved current store's id into credential lookups so
 * customer authentication never crosses store boundaries. The same email
 * address may exist in different stores as separate accounts.
 */
class CustomerUserProvider extends EloquentUserProvider
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        $storeId = app(CurrentStore::class)->id();

        if ($storeId === null) {
            return null;
        }

        $credentials['store_id'] = $storeId;

        return parent::retrieveByCredentials($credentials);
    }
}
