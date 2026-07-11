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
            if ($model->getAttribute('store_id') === null && app()->bound('current_store')) {
                /** @var Store $store */
                $store = app('current_store');

                $model->setAttribute('store_id', $store->getKey());
            }
        });
    }
}
