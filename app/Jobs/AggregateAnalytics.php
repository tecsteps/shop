<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Roll up raw analytics events into analytics_daily per spec 05 section 14.2.
 * Scheduled daily at 01:00 UTC in routes/console.php.
 */
class AggregateAnalytics implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?string $date = null) {}

    public function handle(): void
    {
        $target = $this->date !== null ? CarbonImmutable::parse($this->date) : CarbonImmutable::yesterday();

        foreach (Store::query()->pluck('id') as $storeId) {
            $this->aggregateStore((int) $storeId, $target);
        }
    }

    protected function aggregateStore(int $storeId, CarbonImmutable $day): void
    {
        $from = $day->startOfDay();
        $to = $day->endOfDay();
        $dateString = $day->toDateString();

        $completed = AnalyticsEvent::query()
            ->where('store_id', $storeId)
            ->where('type', 'checkout_completed')
            ->whereBetween('occurred_at', [$from, $to])
            ->get(['properties_json']);

        $ordersCount = $completed->count();
        $revenueAmount = $completed->sum(
            fn ($event): int => (int) (is_array($event->properties_json) ? ($event->properties_json['total_amount'] ?? 0) : 0)
        );

        $visitsCount = AnalyticsEvent::query()
            ->where('store_id', $storeId)
            ->where('type', 'page_view')
            ->whereBetween('occurred_at', [$from, $to])
            ->distinct('session_id')
            ->count('session_id');

        $addToCartCount = $this->countForDay($storeId, 'add_to_cart', $from, $to);
        $checkoutStartedCount = $this->countForDay($storeId, 'checkout_started', $from, $to);

        AnalyticsDaily::query()->updateOrCreate(
            ['store_id' => $storeId, 'date' => $dateString],
            [
                'orders_count' => $ordersCount,
                'revenue_amount' => $revenueAmount,
                'aov_amount' => $ordersCount > 0 ? intdiv($revenueAmount, $ordersCount) : 0,
                'visits_count' => $visitsCount,
                'add_to_cart_count' => $addToCartCount,
                'checkout_started_count' => $checkoutStartedCount,
                'checkout_completed_count' => $ordersCount,
            ]
        );
    }

    protected function countForDay(int $storeId, string $type, CarbonImmutable $from, CarbonImmutable $to): int
    {
        return AnalyticsEvent::query()
            ->where('store_id', $storeId)
            ->where('type', $type)
            ->whereBetween('occurred_at', [$from, $to])
            ->count();
    }
}
