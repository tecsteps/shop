<?php

namespace App\Jobs;

use App\Enums\CartStatus;
use App\Models\Cart;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupAbandonedCarts implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cart::query()
            ->withoutGlobalScopes()
            ->where('status', CartStatus::Active->value)
            ->where('updated_at', '<', now()->subDays(14))
            ->update(['status' => CartStatus::Abandoned->value]);
    }
}
