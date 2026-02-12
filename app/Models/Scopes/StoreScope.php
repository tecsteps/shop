<?php

namespace App\Models\Scopes;

use App\Support\CurrentStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StoreScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->bound(CurrentStore::class)) {
            return;
        }

        $storeId = app(CurrentStore::class)->id();

        if (! $storeId) {
            return;
        }

        $builder->where($model->qualifyColumn('store_id'), $storeId);
    }
}
