<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReindexSearch implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $storeId) {}

    public function handle(SearchService $search): void
    {
        $store = Store::query()->find($this->storeId);

        if ($store === null) {
            return;
        }

        $search->reindex($store);
    }
}
