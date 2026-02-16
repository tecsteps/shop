<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AggregateAnalytics implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $yesterday = now()->subDay()->toDateString();

        Store::all()->each(function (Store $store) use ($yesterday): void {
            $this->aggregateForStore($store, $yesterday);
        });
    }

    protected function aggregateForStore(Store $store, string $date): void
    {
        $events = AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereRaw('DATE(created_at) = ?', [$date]);

        $metrics = [
            'orders_count' => (clone $events)->where('type', 'order_placed')->count(),
            'revenue_amount' => (int) (clone $events)->where('type', 'order_placed')
                ->get()
                ->sum(fn ($e) => $e->properties_json['total'] ?? 0),
            'visits_count' => (clone $events)->where('type', 'page_view')->count(),
            'add_to_cart_count' => (clone $events)->where('type', 'add_to_cart')->count(),
            'checkout_started_count' => (clone $events)->where('type', 'checkout_started')->count(),
            'checkout_completed_count' => (clone $events)->where('type', 'checkout_completed')->count(),
        ];

        $metrics['aov_amount'] = $metrics['orders_count'] > 0
            ? (int) ($metrics['revenue_amount'] / $metrics['orders_count'])
            : 0;

        AnalyticsDaily::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id, 'date' => $date],
            $metrics,
        );
    }
}
