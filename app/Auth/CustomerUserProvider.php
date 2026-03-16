<?php

namespace App\Auth;

use App\Models\Customer;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;

class CustomerUserProvider extends EloquentUserProvider
{
    public function __construct(Hasher $hasher)
    {
        parent::__construct($hasher, Customer::class);
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $store) {
            return null;
        }

        $query = Customer::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id);

        foreach ($credentials as $key => $value) {
            if ($key === 'password') {
                continue;
            }
            $query->where($key, $value);
        }

        return $query->first();
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $store) {
            return Customer::query()->withoutGlobalScopes()->find($identifier);
        }

        return Customer::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->find($identifier);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        $query = Customer::query()->withoutGlobalScopes();

        if ($store) {
            $query->where('store_id', $store->id);
        }

        $model = $query->find($identifier);

        if (! $model) {
            return null;
        }

        $rememberToken = $model->getRememberToken();

        return $rememberToken && hash_equals($rememberToken, $token) ? $model : null;
    }
}
