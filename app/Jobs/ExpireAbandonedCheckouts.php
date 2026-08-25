<?php

namespace App\Jobs;

use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireAbandonedCheckouts implements ShouldQueue
{
    use Queueable;

    public function handle(CheckoutService $checkoutService): void
    {
        Checkout::whereNotIn('status', ['completed', 'expired'])
            ->where('updated_at', '<', now()->subHours(24))
            ->get()
            ->each(fn (Checkout $checkout) => $checkoutService->expireCheckout($checkout));
    }
}
