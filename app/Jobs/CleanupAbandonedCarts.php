<?php

namespace App\Jobs;

use App\Enums\CartStatus;
use App\Models\Cart;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupAbandonedCarts implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Cart::query()
            ->where('status', CartStatus::Active)
            ->where('updated_at', '<', now()->subDays(14))
            ->update(['status' => CartStatus::Abandoned]);
    }
}
