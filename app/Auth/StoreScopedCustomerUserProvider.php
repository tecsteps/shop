<?php

namespace App\Auth;

use App\Models\Store;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Builder;

class StoreScopedCustomerUserProvider extends EloquentUserProvider
{
    /** @param \Illuminate\Database\Eloquent\Model|null $model */
    protected function newModelQuery($model = null): Builder
    {
        $query = parent::newModelQuery($model);
        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $store instanceof Store) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($query->getModel()->qualifyColumn('store_id'), $store->id);
    }
}
