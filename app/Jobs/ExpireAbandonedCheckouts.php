<?php

namespace App\Jobs;

use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireAbandonedCheckouts implements ShouldQueue
{
    use Queueable;

    /**
     * Expire past-due active checkouts and release reserved inventory.
     */
    public function handle(CheckoutService $checkoutService): void
    {
        Checkout::query()
            ->withoutGlobalScopes()
            ->whereNotIn('status', ['completed', 'expired'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->each(fn (Checkout $checkout) => $checkoutService->expireCheckout($checkout));
    }
}
