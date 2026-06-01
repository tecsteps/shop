<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pre-aggregated daily store metrics, populated by {@see \App\Jobs\AggregateAnalytics}.
 *
 * Composite primary key (`store_id`, `date`). All `_amount` columns are integers
 * in minor units (cents). No timestamps.
 */
class AnalyticsDaily extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analytics_daily';

    /**
     * The composite key has no single auto-incrementing column.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * This table carries no timestamp columns.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'date',
        'orders_count',
        'revenue_amount',
        'aov_amount',
        'visits_count',
        'add_to_cart_count',
        'checkout_started_count',
        'checkout_completed_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orders_count' => 'integer',
            'revenue_amount' => 'integer',
            'aov_amount' => 'integer',
            'visits_count' => 'integer',
            'add_to_cart_count' => 'integer',
            'checkout_started_count' => 'integer',
            'checkout_completed_count' => 'integer',
        ];
    }

    /**
     * The store these metrics belong to.
     *
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
