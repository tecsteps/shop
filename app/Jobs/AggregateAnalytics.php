<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class AggregateAnalytics implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?string $date = null) {}

    public function handle(AnalyticsService $analytics): void
    {
        $date = $this->date ? Carbon::parse($this->date) : now()->subDay();

        Store::query()
            ->orderBy('id')
            ->each(fn (Store $store): mixed => $analytics->aggregateDate($store, $date));
    }
}
