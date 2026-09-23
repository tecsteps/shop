<?php

namespace App\Jobs;

use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireAbandonedCheckouts implements ShouldQueue
{
    use Queueable;

    public function handle(CheckoutService $checkouts): void
    {
        Checkout::withoutGlobalScopes()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNotIn('status', ['completed', 'expired'])
            ->orderBy('id')
            ->chunkById(100, function ($expiredCheckouts) use ($checkouts): void {
                foreach ($expiredCheckouts as $checkout) {
                    $checkouts->expire($checkout);
                }
            });
    }
}
