<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomerUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * Injects store_id from the resolved current store into the query
     * so that customer emails are unique per store, not globally.
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $credentials = $this->injectStoreId($credentials);

        return parent::retrieveByCredentials($credentials);
    }

    /**
     * Validate a user against the given credentials.
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return parent::validateCredentials($user, $credentials);
    }

    /**
     * Inject the current store_id into credential queries.
     *
     * @param  array<string, mixed>  $credentials
     * @return array<string, mixed>
     */
    protected function injectStoreId(array $credentials): array
    {
        if (! isset($credentials['store_id']) && app()->bound('current_store')) {
            $credentials['store_id'] = app('current_store')->id;
        }

        return $credentials;
    }
}
