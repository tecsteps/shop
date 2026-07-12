<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class AggregateAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly ?string $date = null) {}

    public function handle(AnalyticsService $analytics): void
    {
        $date = $this->date ?? now()->subDay()->toDateString();
        Store::query()->where('status', 'active')->each(fn (Store $store) => $analytics->aggregate($store, $date));
    }
}
