<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StoreScope implements Scope
{
    /**
     * Apply the tenant isolation scope to the given query.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->bound('current_store')) {
            return;
        }

        $store = app('current_store');

        if ($store instanceof Model && $store->getKey() !== null) {
            $builder->where($model->getTable().'.store_id', $store->getKey());
        }
    }
}
