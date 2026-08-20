<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Order;
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
            $events = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $store->getKey())->whereDate('created_at', $date)->get();
            $orders = Order::withoutGlobalScopes()->where('store_id', $store->getKey())->whereDate('placed_at', $date)->whereIn('financial_status', ['paid', 'partially_refunded'])->get();
            $revenue = (int) $orders->sum('total_amount');

            AnalyticsDaily::withoutGlobalScopes()->newQuery()->updateOrInsert(
                ['store_id' => $store->getKey(), 'date' => $date->toDateString()],
                [
                    'orders_count' => $orders->count(),
                    'revenue_amount' => $revenue,
                    'aov_amount' => $orders->count() > 0 ? intdiv($revenue, $orders->count()) : 0,
                    'visits_count' => $events->where('type', 'page_view')->count(),
                    'add_to_cart_count' => $events->where('type', 'add_to_cart')->count(),
                    'checkout_started_count' => $events->where('type', 'checkout_started')->count(),
                    'checkout_completed_count' => $events->where('type', 'checkout_completed')->count(),
                ],
            );
        }
    }
}
