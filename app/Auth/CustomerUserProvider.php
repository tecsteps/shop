<?php

namespace App\Auth;

use App\Models\Store;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomerUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $credentials = $this->scopedCredentials($credentials);

        return parent::retrieveByCredentials($credentials);
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        $query = $this->createModel()->newQuery();

        if ($storeId = $this->currentStoreId()) {
            $query->where('store_id', $storeId);
        }

        return $query->find($identifier);
    }

    protected function scopedCredentials(array $credentials): array
    {
        if ($storeId = $this->currentStoreId()) {
            $credentials['store_id'] = $storeId;
        }

        return $credentials;
    }

    protected function currentStoreId(): ?int
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        $store = app('current_store');

        return $store instanceof Store ? $store->id : null;
    }
}
