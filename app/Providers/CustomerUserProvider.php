<?php

namespace App\Providers;

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

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        /** @var \App\Models\Store $currentStore */
        $currentStore = app('current_store');
        $storeId = $currentStore->id;

        $query = Customer::query()->where('store_id', $storeId);

        foreach ($credentials as $key => $value) {
            if (! str_contains($key, 'password')) {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }
}
