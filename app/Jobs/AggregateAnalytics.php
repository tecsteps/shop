<?php

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AggregateAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly ?string $date = null) {}

    public function handle(): void
    {
        $date = $this->date !== null
            ? CarbonImmutable::parse($this->date)->startOfDay()
            : CarbonImmutable::yesterday()->startOfDay();

        $dateString = $date->toDateString();
        $start = $date->startOfDay();
        $end = $date->endOfDay();

        Store::query()->each(function (Store $store) use ($dateString, $start, $end): void {
            $orders = Order::query()
                ->where('store_id', $store->id)
                ->whereNotNull('placed_at')
                ->whereBetween('placed_at', [$start, $end])
                ->get(['id', 'total_amount']);

            $ordersCount = $orders->count();
            $revenue = (int) $orders->sum('total_amount');
            $aov = $ordersCount > 0 ? (int) round($revenue / $ordersCount) : 0;

            $events = AnalyticsEvent::query()
                ->where('store_id', $store->id)
                ->whereBetween('occurred_at', [$start, $end])
                ->get(['type', 'session_id']);

            $visits = (int) $events
                ->where('type', 'page_view')
                ->pluck('session_id')
                ->filter()
                ->unique()
                ->count();

            $addToCart = (int) $events->where('type', 'add_to_cart')->count();
            $checkoutStarted = (int) $events->where('type', 'checkout_started')->count();
            $checkoutCompleted = (int) $events->where('type', 'checkout_completed')->count();

            DB::table('analytics_daily')->updateOrInsert(
                ['store_id' => $store->id, 'date' => $dateString],
                [
                    'orders_count' => $ordersCount,
                    'revenue_amount' => $revenue,
                    'aov_amount' => $aov,
                    'visits_count' => $visits,
                    'add_to_cart_count' => $addToCart,
                    'checkout_started_count' => $checkoutStarted,
                    'checkout_completed_count' => $checkoutCompleted,
                ]
            );
        });
    }
}
