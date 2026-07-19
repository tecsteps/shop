<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomerUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * Injects the current store's id into the credential query so customer
     * authentication never crosses store boundaries. The store is resolved
     * by the ResolveStore middleware before any login attempt.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (app()->bound('current_store')) {
            $credentials['store_id'] = app('current_store')->getKey();
        }

        return parent::retrieveByCredentials($credentials);
    }
}
