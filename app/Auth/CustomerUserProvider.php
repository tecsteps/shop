<?php

namespace App\Auth;

use App\Models\Customer;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

final class CustomerUserProvider extends EloquentUserProvider
{
    /** @param array<string, mixed> $credentials */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials) || (count($credentials) === 1 && array_key_exists('password', $credentials))) {
            return null;
        }

        $query = Customer::withoutGlobalScopes();
        if (app()->bound('current_store')) {
            $query->where('store_id', app('current_store')->id);
        } else {
            return null;
        }

        foreach ($credentials as $key => $value) {
            if (! str_contains($key, 'password')) {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }
}
