<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AggregateAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public ?Store $store = null, public ?string $date = null) {}

    public function handle(): void
    {
        $date = CarbonImmutable::parse($this->date ?? now()->subDay()->toDateString());
        $stores = $this->store === null ? Store::query()->get() : collect([$this->store]);

        foreach ($stores as $store) {
            $events = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $store->getKey())->whereDate('occurred_at', $date)->get();
            $completed = $events->where('type', 'checkout_completed');
            $ordersCount = $completed->count();
            $revenue = (int) $completed->sum(fn (AnalyticsEvent $event): int => (int) ($event->properties_json['total_amount'] ?? $event->properties_json['order_total_amount'] ?? 0));

            AnalyticsDaily::withoutGlobalScopes()->newQuery()->updateOrInsert(
                ['store_id' => $store->getKey(), 'date' => $date->toDateString()],
                [
                    'orders_count' => $ordersCount,
                    'revenue_amount' => $revenue,
                    'aov_amount' => $ordersCount > 0 ? intdiv($revenue, $ordersCount) : 0,
                    'visits_count' => $events->where('type', 'page_view')->pluck('session_id')->filter()->unique()->count(),
                    'add_to_cart_count' => $events->where('type', 'add_to_cart')->count(),
                    'checkout_started_count' => $events->where('type', 'checkout_started')->count(),
                    'checkout_completed_count' => $events->where('type', 'checkout_completed')->count(),
                ],
            );
        }
    }
}
