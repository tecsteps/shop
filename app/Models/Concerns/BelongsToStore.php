<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StoreScope;
use App\Models\Store;
use App\Support\Tenant\CurrentStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope(app(StoreScope::class));

        static::creating(function (Model $model): void {
            if ($model->getAttribute('store_id') !== null) {
                return;
            }

            $storeId = self::resolveCurrentStoreId();

            if ($storeId !== null) {
                $model->setAttribute('store_id', $storeId);
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    private static function resolveCurrentStoreId(): ?int
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
