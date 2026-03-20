<?php

namespace App\Services;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Support\Collection;

class AnalyticsService
{
    public function track(
        Store $store,
        string $type,
        array $properties = [],
        ?string $sessionId = null,
        ?int $customerId = null,
    ): void {
        AnalyticsEvent::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'type' => $type,
            'properties_json' => $properties,
            'session_id' => $sessionId,
            'customer_id' => $customerId,
            'created_at' => now()->toIso8601String(),
        ]);
    }

    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->orderBy('date')
            ->get();
    }
}
