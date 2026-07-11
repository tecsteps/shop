<?php

namespace App\Jobs;

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Scopes\StoreScope;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class AggregateAnalytics implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public ?string $date = null) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $date = CarbonImmutable::parse($this->date ?? now('UTC')->subDay()->toDateString(), 'UTC');
        $eventsByStore = AnalyticsEvent::withoutGlobalScope(StoreScope::class)
            ->whereBetween('created_at', [$date->startOfDay(), $date->endOfDay()])
            ->get()
            ->groupBy('store_id');

        foreach ($eventsByStore as $storeId => $events) {
            $completedCheckouts = $events->where('type', AnalyticsEventType::CheckoutCompleted);
            $ordersCount = $completedCheckouts->count();
            $revenueAmount = $completedCheckouts->sum(
                fn (AnalyticsEvent $event): int => (int) data_get($event->properties_json, 'total_amount', 0),
            );

            DB::table((new AnalyticsDaily)->getTable())->updateOrInsert(
                ['store_id' => (int) $storeId, 'date' => $date->toDateString()],
                [
                    'orders_count' => $ordersCount,
                    'revenue_amount' => $revenueAmount,
                    'aov_amount' => $ordersCount === 0 ? 0 : intdiv($revenueAmount, $ordersCount),
                    'visits_count' => $events
                        ->where('type', AnalyticsEventType::PageView)
                        ->pluck('session_id')
                        ->filter()
                        ->unique()
                        ->count(),
                    'add_to_cart_count' => $events->where('type', AnalyticsEventType::AddToCart)->count(),
                    'checkout_started_count' => $events->where('type', AnalyticsEventType::CheckoutStarted)->count(),
                    'checkout_completed_count' => $ordersCount,
                ],
            );
        }
    }
}
