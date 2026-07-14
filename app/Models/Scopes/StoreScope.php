<?php

namespace App\Models\Scopes;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StoreScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->bound('current_store')) {
            return;
        }

        $store = app('current_store');
        $storeId = $store instanceof Store ? $store->getKey() : $store;

        $builder->where($model->qualifyColumn('store_id'), $storeId);
    }
}
