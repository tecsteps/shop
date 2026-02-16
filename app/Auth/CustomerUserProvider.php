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
        if (! app()->bound('current_store')) {
            return null;
        }

        $query = Customer::withoutGlobalScopes()
            ->where('store_id', app('current_store')->id);

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
        return Customer::withoutGlobalScopes()->find($identifier);
    }
}
