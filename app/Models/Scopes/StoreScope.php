<?php

namespace App\Models\Scopes;

use App\Tenancy\CurrentStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Automatically scopes queries to the resolved current store.
 *
 * When no store is bound (console, seeders, platform-admin contexts) the
 * scope is a no-op. Cross-store access uses withoutGlobalScope(StoreScope::class).
 */
class StoreScope implements Scope
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $storeId = app(CurrentStore::class)->id();

        if ($storeId === null) {
            return;
        }

        $builder->where($model->getTable().'.store_id', $storeId);
    }
}
