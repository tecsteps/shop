<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StoreScope;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToStore
{
    /**
     * Boot the tenant trait: apply the global store scope and default
     * the store_id attribute from the bound store on creation.
     */
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope(new StoreScope);

        static::creating(function (Model $model): void {
            if (empty($model->getAttribute('store_id')) && app()->bound('current_store')) {
                $model->setAttribute('store_id', app('current_store')->getKey());
            }
        });
    }

    /**
     * Get the store that owns the model.
     *
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
