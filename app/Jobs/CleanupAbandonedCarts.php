<?php

namespace App\Jobs;

use App\Models\Cart;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupAbandonedCarts implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cart::where('status', 'active')
            ->where('updated_at', '<', now()->subDays(14))
            ->update(['status' => 'abandoned']);
    }
}
