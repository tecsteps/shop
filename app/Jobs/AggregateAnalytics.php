<?php

namespace App\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class AggregateAnalytics implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ?string $date = null) {}

    public function handle(): void
    {
        $targetDate = $this->date
            ? CarbonImmutable::parse($this->date, 'UTC')->toDateString()
            : CarbonImmutable::now('UTC')->subDay()->toDateString();

        $rows = DB::table('analytics_events')
            ->selectRaw('store_id')
            ->selectRaw("SUM(CASE WHEN type = 'checkout_completed' THEN 1 ELSE 0 END) as checkout_completed_count")
            ->selectRaw("SUM(CASE WHEN type = 'checkout_completed' THEN COALESCE(
                CAST(json_extract(properties_json, '$.order_total_amount') AS INTEGER),
                CAST(json_extract(properties_json, '$.total_amount') AS INTEGER),
                CAST(json_extract(properties_json, '$.revenue_amount') AS INTEGER),
                0
            ) ELSE 0 END) as revenue_amount")
            ->selectRaw("COUNT(DISTINCT CASE WHEN type = 'page_view' THEN session_id END) as visits_count")
            ->selectRaw("SUM(CASE WHEN type = 'add_to_cart' THEN 1 ELSE 0 END) as add_to_cart_count")
            ->selectRaw("SUM(CASE WHEN type = 'checkout_started' THEN 1 ELSE 0 END) as checkout_started_count")
            ->whereRaw('date(COALESCE(occurred_at, created_at)) = ?', [$targetDate])
            ->groupBy('store_id')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $upserts = $rows->map(static function (object $row) use ($targetDate): array {
            $ordersCount = (int) $row->checkout_completed_count;
            $revenueAmount = (int) $row->revenue_amount;

            return [
                'store_id' => (int) $row->store_id,
                'date' => $targetDate,
                'orders_count' => $ordersCount,
                'revenue_amount' => $revenueAmount,
                'aov_amount' => $ordersCount > 0 ? intdiv($revenueAmount, $ordersCount) : 0,
                'visits_count' => (int) $row->visits_count,
                'add_to_cart_count' => (int) $row->add_to_cart_count,
                'checkout_started_count' => (int) $row->checkout_started_count,
                'checkout_completed_count' => $ordersCount,
            ];
        })->all();

        DB::table('analytics_daily')->upsert(
            $upserts,
            ['store_id', 'date'],
            [
                'orders_count',
                'revenue_amount',
                'aov_amount',
                'visits_count',
                'add_to_cart_count',
                'checkout_started_count',
                'checkout_completed_count',
            ],
        );
    }
}
