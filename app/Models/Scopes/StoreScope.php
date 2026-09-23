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
        $store = app()->bound('current_store') ? app('current_store') : null;

        if ($store instanceof Store) {
            $builder->where($model->qualifyColumn('store_id'), $store->getKey());
        }
    }
}
