<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pre-aggregated daily metrics per store. The table uses a composite
 * primary key (store_id, date), which Eloquent cannot address directly,
 * so rows are written via upsert and read with explicit where clauses.
 */
class AnalyticsDaily extends Model
{
    /** @use HasFactory<\Database\Factories\AnalyticsDailyFactory> */
    use HasFactory;

    protected $table = 'analytics_daily';

    protected $primaryKey = null;

    public $incrementing = false;

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

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Scope the query to a store and inclusive ISO date range.
     *
     * @param  Builder<AnalyticsDaily>  $query
     * @return Builder<AnalyticsDaily>
     */
    public function scopeForStoreBetween(Builder $query, Store $store, string $startDate, string $endDate): Builder
    {
        return $query
            ->where('store_id', $store->getKey())
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->orderBy('date');
    }
}
