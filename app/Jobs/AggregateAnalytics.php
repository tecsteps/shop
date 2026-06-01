<?php

namespace App\Jobs;

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Rolls raw analytics events and order data into pre-aggregated `analytics_daily`
 * rows for a single calendar date, per store.
 *
 * Idempotent: each (store, date) row is recomputed and upserted, so re-running
 * for the same date never double-counts. Runs daily for the previous day; an
 * explicit date may be supplied for backfills/tests. Amounts are minor units.
 */
class AggregateAnalytics implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string|null  $date  Target date (YYYY-MM-DD); defaults to yesterday.
     */
    public function __construct(private readonly ?string $date = null) {}

    public function handle(): void
    {
        $date = $this->date ?? Carbon::yesterday()->toDateString();

        $start = Carbon::parse($date)->startOfDay();
        $end = Carbon::parse($date)->endOfDay();

        $storeIds = $this->storeIdsWithActivity($start, $end);

        foreach ($storeIds as $storeId) {
            $this->aggregateStore((int) $storeId, $date, $start, $end);
        }
    }

    /**
     * The distinct store ids that have either events or orders on the date.
     *
     * @return list<int>
     */
    private function storeIdsWithActivity(Carbon $start, Carbon $end): array
    {
        $eventStores = AnalyticsEvent::query()
            ->withoutGlobalScopes()
            ->whereBetween('created_at', [$start, $end])
            ->distinct()
            ->pluck('store_id');

        $orderStores = Order::query()
            ->withoutGlobalScopes()
            ->whereBetween('placed_at', [$start, $end])
            ->distinct()
            ->pluck('store_id');

        return $eventStores->merge($orderStores)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function aggregateStore(int $storeId, string $date, Carbon $start, Carbon $end): void
    {
        $events = AnalyticsEvent::query()
            ->withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereBetween('created_at', [$start, $end]);

        $eventCounts = (clone $events)
            ->select('type', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        $visits = (clone $events)
            ->whereNotNull('session_id')
            ->distinct()
            ->count('session_id');

        $orderStats = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereBetween('placed_at', [$start, $end])
            ->selectRaw('COUNT(*) as orders_count, COALESCE(SUM(total_amount), 0) as revenue_amount')
            ->first();

        $ordersCount = (int) ($orderStats->orders_count ?? 0);
        $revenue = (int) ($orderStats->revenue_amount ?? 0);

        AnalyticsDaily::query()->updateOrCreate(
            ['store_id' => $storeId, 'date' => $date],
            [
                'orders_count' => $ordersCount,
                'revenue_amount' => $revenue,
                'aov_amount' => $ordersCount > 0 ? intdiv($revenue, $ordersCount) : 0,
                'visits_count' => (int) $visits,
                'add_to_cart_count' => (int) ($eventCounts[AnalyticsEventType::AddToCart->value] ?? 0),
                'checkout_started_count' => (int) ($eventCounts[AnalyticsEventType::CheckoutStarted->value] ?? 0),
                'checkout_completed_count' => (int) ($eventCounts[AnalyticsEventType::CheckoutCompleted->value] ?? 0),
            ],
        );
    }
}
