<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StoreScope implements Scope
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        if ($store) {
            $builder->where($model->getTable().'.store_id', $store->id);
        }
    }
}
