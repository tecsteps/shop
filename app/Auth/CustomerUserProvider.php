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

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $credentials = $this->injectStoreId($credentials);

        return parent::retrieveByCredentials($credentials);
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array<string, mixed>
     */
    protected function injectStoreId(array $credentials): array
    {
        if (! isset($credentials['store_id']) && app()->bound('current_store')) {
            $credentials['store_id'] = app('current_store')->id;
        }

        return $credentials;
    }
}
