<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StoreScope;
use App\Models\Store;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope(new StoreScope);

        static::creating(function ($model): void {
            if (! $model->store_id && app()->bound('current_store')) {
                $store = app('current_store');

                if ($store instanceof Store) {
                    $model->store_id = $store->id;
                }
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
