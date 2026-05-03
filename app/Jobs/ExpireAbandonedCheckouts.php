<?php

namespace App\Jobs;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireAbandonedCheckouts implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(CheckoutService $checkouts): void
    {
        Checkout::withoutGlobalScopes()
            ->whereNotIn('status', [CheckoutStatus::Completed, CheckoutStatus::Expired])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get()
            ->each(fn (Checkout $checkout): mixed => $checkouts->expireCheckout($checkout));
    }
}
