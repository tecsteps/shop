<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Track a raw analytics event.
     *
     * @param  array<string, mixed>  $properties
     */
    public function track(Store $store, string $type, array $properties = [], ?string $sessionId = null, ?int $customerId = null): void
    {
        AnalyticsEvent::query()->create([
            'store_id' => $store->id,
            'type' => $type,
            'properties_json' => $properties,
            'session_id' => $sessionId,
            'customer_id' => $customerId,
            'occurred_at' => now(),
            'created_at' => now(),
        ]);
    }

    /**
     * Get pre-aggregated daily metrics for a date range.
     *
     * @return Collection<int, \App\Models\AnalyticsDaily>
     */
    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return DB::table('analytics_daily')
            ->where('store_id', $store->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }
}
