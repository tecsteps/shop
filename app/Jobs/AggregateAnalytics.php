<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AggregateAnalytics implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $date = now()->subDay()->toDateString();

        Store::query()->each(function (Store $store) use ($date): void {
            $events = AnalyticsEvent::query()
                ->where('store_id', $store->id)
                ->whereDate('created_at', $date)
                ->get();

            $orders = Order::query()
                ->where('store_id', $store->id)
                ->whereDate('placed_at', $date)
                ->get();

            $revenue = (int) $orders->sum('total_amount');
            $count = $orders->count();

            AnalyticsDaily::query()->updateOrCreate(
                ['store_id' => $store->id, 'date' => $date],
                [
                    'orders_count' => $count,
                    'revenue_amount' => $revenue,
                    'aov_amount' => $count > 0 ? intdiv($revenue, $count) : 0,
                    'visits_count' => $events->where('type', 'page_view')->pluck('session_id')->unique()->count(),
                    'add_to_cart_count' => $events->where('type', 'add_to_cart')->count(),
                    'checkout_started_count' => $events->where('type', 'checkout_started')->count(),
                    'checkout_completed_count' => $events->where('type', 'checkout_completed')->count(),
                ],
            );
        });
    }
}
