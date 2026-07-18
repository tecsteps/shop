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

    public function handle(CheckoutService $checkoutService): void
    {
        Checkout::query()
            ->whereNotIn('status', [CheckoutStatus::Completed, CheckoutStatus::Expired])
            ->where('updated_at', '<', now()->subDay())
            ->chunkById(100, function ($checkouts) use ($checkoutService): void {
                foreach ($checkouts as $checkout) {
                    $checkoutService->expireCheckout($checkout);
                }
            });
    }
}
