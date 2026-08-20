<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StoreScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->bound('current_store')) {
            $builder->whereNull($model->qualifyColumn('store_id'));

            return;
        }

        $currentStore = app('current_store');

        if ($currentStore instanceof \App\Models\Store) {
            $builder->where($model->qualifyColumn('store_id'), $currentStore->getKey());

            return;
        }

        $builder->whereNull($model->qualifyColumn('store_id'));
    }
}
