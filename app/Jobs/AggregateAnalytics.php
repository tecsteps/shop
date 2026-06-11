<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Rolls raw analytics_events up into analytics_daily rows (spec 05 section
 * 14.2). Runs daily at 01:00 UTC for the previous day; idempotent because
 * each (store_id, date) row is fully recomputed and upserted.
 */
class AggregateAnalytics implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string|null  $date  ISO date (Y-m-d) to aggregate; defaults to yesterday.
     */
    public function __construct(public ?string $date = null) {}

    /**
     * Aggregate the day's events for every store.
     */
    public function handle(): void
    {
        $day = CarbonImmutable::parse($this->date ?? now()->subDay()->toDateString())->startOfDay();

        Store::query()->each(function (Store $store) use ($day): void {
            $this->aggregateStoreDay($store, $day);
        });
    }

    protected function aggregateStoreDay(Store $store, CarbonImmutable $day): void
    {
        $events = AnalyticsEvent::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('created_at', '>=', $day)
            ->where('created_at', '<', $day->addDay());

        $countsByType = (clone $events)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $visitsCount = (clone $events)
            ->where('type', 'page_view')
            ->distinct()
            ->count('session_id');

        $ordersCount = (int) ($countsByType['checkout_completed'] ?? 0);

        $revenueAmount = (clone $events)
            ->where('type', 'checkout_completed')
            ->get()
            ->sum(fn (AnalyticsEvent $event): int => (int) ($event->properties_json['total_amount'] ?? 0));

        if ($ordersCount === 0 && $visitsCount === 0 && $countsByType->isEmpty()) {
            return;
        }

        AnalyticsDaily::query()->upsert(
            [[
                'store_id' => $store->getKey(),
                'date' => $day->toDateString(),
                'orders_count' => $ordersCount,
                'revenue_amount' => $revenueAmount,
                'aov_amount' => $ordersCount > 0 ? intdiv($revenueAmount, $ordersCount) : 0,
                'visits_count' => $visitsCount,
                'add_to_cart_count' => (int) ($countsByType['add_to_cart'] ?? 0),
                'checkout_started_count' => (int) ($countsByType['checkout_started'] ?? 0),
                'checkout_completed_count' => (int) ($countsByType['checkout_completed'] ?? 0),
            ]],
            ['store_id', 'date'],
            [
                'orders_count', 'revenue_amount', 'aov_amount', 'visits_count',
                'add_to_cart_count', 'checkout_started_count', 'checkout_completed_count',
            ],
        );
    }
}
