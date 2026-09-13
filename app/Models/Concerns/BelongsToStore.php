<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StoreScope;
use App\Tenancy\CurrentStore;
use Illuminate\Database\Eloquent\Model;

/**
 * Applies the StoreScope global scope and auto-fills store_id on creation
 * from the resolved current store (defense-in-depth, per spec 05 section 1.2).
 */
trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope(new StoreScope);

        static::creating(function (Model $model): void {
            if (empty($model->getAttribute('store_id')) && app(CurrentStore::class)->has()) {
                $model->setAttribute('store_id', app(CurrentStore::class)->id());
            }
        });
    }
}
