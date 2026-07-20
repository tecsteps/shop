<?php

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use App\Models\Scopes\StoreScope;
use App\Services\AnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

/**
 * Roll up raw analytics events into analytics_daily rows (spec 05 §14.2).
 * Runs daily at 01:00 UTC for the previous day; a specific date (Y-m-d) can
 * be passed to re-aggregate. Values are recomputed from the raw events and
 * upserted per store + date, so re-runs are idempotent.
 */
class AggregateAnalytics implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(public ?string $date = null) {}

    /**
     * Execute the job.
     */
    public function handle(AnalyticsService $analytics): void
    {
        $date = $this->date ?? now()->subDay()->toDateString();

        $storeIds = AnalyticsEvent::withoutGlobalScope(StoreScope::class)
            ->whereRaw('DATE(occurred_at) = ?', [$date])
            ->distinct()
            ->pluck('store_id');

        foreach ($storeIds as $storeId) {
            DB::table('analytics_daily')->updateOrInsert(
                ['store_id' => (int) $storeId, 'date' => $date],
                $analytics->aggregateForDate((int) $storeId, $date),
            );
        }
    }
}
