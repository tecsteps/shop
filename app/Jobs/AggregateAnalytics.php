<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class AggregateAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?string $date = null) {}

    public function handle(AnalyticsService $analytics): void
    {
        $date = $this->date ?? Carbon::yesterday()->toDateString();

        Store::query()->each(function (Store $store) use ($analytics, $date): void {
            $analytics->aggregate($store, $date);
        });
    }
}
