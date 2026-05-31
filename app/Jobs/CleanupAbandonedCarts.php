<?php

namespace App\Jobs;

use App\Enums\CartStatus;
use App\Models\Cart;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

/**
 * Marks active carts that have been idle beyond the configured threshold
 * (default 14 days) as abandoned. Runs daily.
 */
class CleanupAbandonedCarts implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $cutoff = Carbon::now()->subDays((int) config('shop.abandoned_cart_days', 14));

        Cart::query()
            ->withoutGlobalScopes()
            ->where('status', CartStatus::Active->value)
            ->where('updated_at', '<', $cutoff)
            ->update(['status' => CartStatus::Abandoned->value]);
    }
}
