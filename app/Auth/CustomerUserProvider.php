<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomerUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $credentials = $this->injectStoreId($credentials);

        return parent::retrieveByCredentials($credentials);
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return parent::validateCredentials($user, $credentials);
    }

    /**
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
