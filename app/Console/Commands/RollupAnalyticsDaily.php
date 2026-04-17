<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\DashboardMetricsService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class RollupAnalyticsDaily extends Command
{
    protected $signature = 'analytics:rollup {--date= : Optional YYYY-MM-DD date; defaults to yesterday}';

    protected $description = 'Aggregate analytics_events and orders into the analytics_daily table';

    public function handle(DashboardMetricsService $metrics): int
    {
        $date = $this->option('date') !== null
            ? CarbonImmutable::parse((string) $this->option('date'))
            : CarbonImmutable::yesterday();

        $date = $date->startOfDay();

        $stores = Store::query()->get();

        foreach ($stores as $store) {
            $metrics->rollupDay($store, $date);
            $this->info(sprintf('Rolled up store %d for %s', $store->getKey(), $date->toDateString()));
        }

        return self::SUCCESS;
    }
}
