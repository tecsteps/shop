<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StoreScope;
use App\Models\Store;

trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope(new StoreScope);

        static::creating(function (object $model): void {
            if ($model->store_id || ! app()->bound('current_store')) {
                return;
            }

            $store = app('current_store');

            if ($store instanceof Store) {
                $model->store_id = $store->getKey();
            }
        });
    }
}
