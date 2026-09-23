<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StoreScope;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;

trait BelongsToStore
{
    protected static function bootBelongsToStore(): void
    {
        static::addGlobalScope(new StoreScope);

        static::creating(function (Model $model): void {
            $store = app()->bound('current_store') ? app('current_store') : null;

            if ($store instanceof Store && blank($model->getAttribute('store_id'))) {
                $model->setAttribute('store_id', $store->getKey());
            }
        });
    }
}
