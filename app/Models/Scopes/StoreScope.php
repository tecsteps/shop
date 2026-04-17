<?php

namespace App\Models\Scopes;

use App\Support\Tenant\CurrentStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StoreScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $storeId = $this->resolveStoreId();

        if ($storeId === null) {
            return;
        }

        $builder->where($model->qualifyColumn('store_id'), '=', $storeId);
    }

    private function resolveStoreId(): ?int
    {
        if (app()->bound(CurrentStore::class)) {
            $store = app(CurrentStore::class);

            if ($store instanceof CurrentStore) {
                return $store->id;
            }
        }

        if (! app()->bound('current_store')) {
            return null;
        }

        $store = app('current_store');

        if ($store instanceof CurrentStore) {
            return $store->id;
        }

        if (is_array($store) && isset($store['id'])) {
            return (int) $store['id'];
        }

        if (is_object($store) && isset($store->id)) {
            return (int) $store->id;
        }

        return null;
    }
}
