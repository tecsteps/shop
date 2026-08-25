<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Model;

trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope(new StoreScope);

        static::creating(function (Model $model) {
            if (empty($model->getAttribute('store_id')) && app()->bound('current_store')) {
                $store = app('current_store');

                if ($store instanceof Model && $store->getKey() !== null) {
                    $model->setAttribute('store_id', $store->getKey());
                }
            }
        });
    }
}
