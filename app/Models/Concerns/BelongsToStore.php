<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StoreScope;

trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope(new StoreScope);

        static::creating(function ($model): void {
            if (app()->bound('current_store') && ! $model->store_id) {
                $model->store_id = app('current_store')->id;
            }
        });
    }
}
