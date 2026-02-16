<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StoreScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->bound('current_store')) {
            $builder->where($model->getTable().'.store_id', app('current_store')->id);
        }
    }
}
