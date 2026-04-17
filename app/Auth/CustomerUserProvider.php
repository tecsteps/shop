<?php

namespace App\Auth;

use App\Models\Store;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CustomerUserProvider extends EloquentUserProvider
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials)) {
            return null;
        }

        $query = $this->newModelQuery();
        $this->applyStoreScope($query);

        foreach ($credentials as $key => $value) {
            if (str_contains($key, 'password')) {
                continue;
            }

            if (is_array($value) || $value instanceof \Illuminate\Contracts\Support\Arrayable) {
                $query->whereIn($key, $value);
            } elseif ($value instanceof \Closure) {
                $value($query);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applyStoreScope(Builder $query): void
    {
        if (! app()->bound('current_store')) {
            return;
        }

        $store = app('current_store');

        if ($store instanceof Store) {
            $query->where('store_id', $store->getKey());
        }
    }
}
