<?php

namespace App\Auth;

use App\Models\Customer;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher;

class CustomerUserProvider extends EloquentUserProvider
{
    public function __construct(Hasher $hasher)
    {
        parent::__construct($hasher, Customer::class);
    }

    public function retrieveByCredentials(array $credentials): ?Customer
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $store) {
            return null;
        }

        $query = Customer::where('store_id', $store->id);

        foreach ($credentials as $key => $value) {
            if ($key === 'password') {
                continue;
            }
            $query->where($key, $value);
        }

        return $query->first();
    }

    public function retrieveById($identifier): ?Customer
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $store) {
            return (new Customer)->newQuery()->find($identifier);
        }

        return Customer::where('store_id', $store->id)->find($identifier);
    }
}
