<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StoreScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * Adds the tenant filter only when a current store is bound in the
     * container (HTTP context). Console, seeder, and queue contexts
     * without a bound store run unscoped.
     *
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->bound('current_store')) {
            $builder->where($model->qualifyColumn('store_id'), app('current_store')->getKey());
        }
    }
}
