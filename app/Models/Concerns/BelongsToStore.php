<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StoreScope;
use App\Models\Store;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks an Eloquent model as belonging to a single store (tenant).
 *
 * Opt in by adding `use BelongsToStore;` to any model whose table carries a
 * `store_id` column. The trait:
 *   1. registers the {@see StoreScope} global scope so reads are constrained to
 *      the current store; and
 *   2. auto-fills `store_id` from the container-bound `current_store` on create
 *      when the attribute has not been set explicitly.
 *
 * Example:
 *
 *     class Product extends Model
 *     {
 *         use BelongsToStore;
 *     }
 *
 * Applied on: products, collections, customers, orders, carts, checkouts,
 * discounts, shipping_zones, themes, pages, navigation_menus, analytics_events,
 * analytics_daily, webhook_subscriptions, inventory_items, search_queries.
 */
trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope(new StoreScope);

        static::creating(function ($model): void {
            if (empty($model->store_id) && app()->bound('current_store')) {
                $model->store_id = app('current_store')->id;
            }
        });
    }

    /**
     * The store that owns this record.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
