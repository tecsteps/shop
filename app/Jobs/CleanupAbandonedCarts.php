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
     * Execute the job.
     */
    public function handle(): void
    {
        Cart::withoutGlobalScopes()
            ->where('status', CartStatus::Active)
            ->where('updated_at', '<', now()->subDays(14))
            ->update(['status' => CartStatus::Abandoned]);
    }
}
