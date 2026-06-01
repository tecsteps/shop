<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Constrains every query on a store-scoped model to the current store.
 *
 * The current store is resolved from the container binding 'current_store',
 * which is set by the ResolveStore middleware. When no store is bound (for
 * example in console commands or seeders), the scope is a no-op so that
 * cross-store maintenance work is still possible. To bypass the scope
 * explicitly on a query, use `Model::withoutGlobalScope(StoreScope::class)`.
 */
class StoreScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->bound('current_store')) {
            return;
        }

        $builder->where($model->getTable().'.store_id', app('current_store')->id);
    }
}
