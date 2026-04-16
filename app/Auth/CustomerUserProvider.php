<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomerUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials)) {
            return null;
        }

        $query = $this->createModel()->newQuery();

        if (app()->bound('current_store')) {
            $query->where('store_id', app('current_store')->id);
        }

        foreach ($credentials as $key => $value) {
            if (str_contains($key, 'password')) {
                continue;
            }

            if (is_array($value) || $value instanceof \Closure) {
                $query->whereIn($key, (array) $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }
}
