<?php

namespace App\Auth;

use App\Models\Store;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomerUserProvider extends EloquentUserProvider
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $credentials = array_filter(
            $credentials,
            fn (mixed $value, string $key): bool => ! str_contains($key, 'password') && $value !== null,
            ARRAY_FILTER_USE_BOTH,
        );

        if ($credentials === []) {
            return null;
        }

        $query = $this->newModelQuery();

        foreach ($credentials as $key => $value) {
            if (is_array($value) || $value instanceof \Closure) {
                continue;
            }

            $query->where($key, $value);
        }

        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $store instanceof Store) {
            return null;
        }

        $query->where('store_id', $store->getKey());

        return $query->first();
    }
}
