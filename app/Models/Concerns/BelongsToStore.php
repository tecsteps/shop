<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StoreScope;
use App\Support\CurrentStore;

trait BelongsToStore
{
    /**
     * Boot the trait.
     */
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope(new StoreScope);

        static::creating(function ($model): void {
            if (! app()->bound(CurrentStore::class)) {
                return;
            }

            if (! empty($model->store_id)) {
                return;
            }

            $storeId = app(CurrentStore::class)->id();

            if ($storeId) {
                $model->store_id = $storeId;
            }
        });
    }
}
