<?php

namespace App\Auth;

use App\Models\Store;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomerUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        if ($store instanceof Store) {
            $credentials['store_id'] = $store->id;
        }

        return parent::retrieveByCredentials($credentials);
    }
}
