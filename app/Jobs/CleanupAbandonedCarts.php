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
     * Carts are considered abandoned after 14 days of inactivity.
     */
    public const int INACTIVE_DAYS = 14;

    /**
     * Mark stale active carts as abandoned.
     */
    public function handle(): void
    {
        Cart::query()
            ->withoutGlobalScopes()
            ->where('status', CartStatus::Active)
            ->where('updated_at', '<', now()->subDays(self::INACTIVE_DAYS))
            ->update(['status' => CartStatus::Abandoned]);
    }
}
