<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;

class CustomerUserProvider extends EloquentUserProvider
{
    public function __construct(Hasher $hasher, string $model)
    {
        parent::__construct($hasher, $model);
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $query = $this->createModel()->newQuery();

        if (app()->bound('current_store')) {
            $query->where('store_id', app('current_store')->id);
        }

        foreach ($credentials as $key => $value) {
            if (! str_contains($key, 'password')) {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $plain = $credentials['password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        $query = $this->createModel()->newQuery();

        if (app()->bound('current_store')) {
            $query->where('store_id', app('current_store')->id);
        }

        return $query->find($identifier);
    }
}
