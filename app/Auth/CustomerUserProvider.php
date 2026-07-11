<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomerUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        $credentials['store_id'] = app('current_store')->id;

        return parent::retrieveByCredentials($credentials);
    }
}
