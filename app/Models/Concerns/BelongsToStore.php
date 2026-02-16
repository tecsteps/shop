<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StoreScope;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;

trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope(new StoreScope);

        static::creating(function (Model $model): void {
            if (app()->bound('current_store') && ! $model->getAttribute('store_id')) {
                /** @var Store $store */
                $store = app('current_store');
                $model->setAttribute('store_id', $store->id);
            }
        });
    }
}
