<?php

namespace App\Auth;

use App\Models\Customer;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

final class CustomerUserProvider extends EloquentUserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        return $this->tenantQuery()->whereKey($identifier)->first();
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        $model = $this->retrieveById($identifier);
        if (! $model instanceof Customer || $model->getRememberTokenName() === '') {
            return null;
        }

        $rememberToken = $model->getRememberToken();

        return $rememberToken !== null && hash_equals($rememberToken, (string) $token) ? $model : null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        if (! $user instanceof Customer || $user->getRememberTokenName() === '') {
            return;
        }

        $tenantCustomer = $this->retrieveById($user->getAuthIdentifier());
        if (! $tenantCustomer instanceof Customer) {
            return;
        }

        parent::updateRememberToken($tenantCustomer, $token);
    }

    /** @param array<string, mixed> $credentials */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials) || (count($credentials) === 1 && array_key_exists('password', $credentials))) {
            return null;
        }

        if (! app()->bound('current_store')) {
            return null;
        }
        $query = $this->tenantQuery();

        foreach ($credentials as $key => $value) {
            if (! str_contains($key, 'password')) {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    private function tenantQuery(): mixed
    {
        $query = Customer::withoutGlobalScopes()->whereRaw('1 = 0');
        if (! app()->bound('current_store')) {
            return $query;
        }

        $store = app('current_store');
        $storeId = is_object($store) ? $store->getKey() : $store;

        return Customer::withoutGlobalScopes()->where('store_id', $storeId);
    }
}
