<?php

namespace App\Jobs;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Expire active checkouts that are past their deadline or idle for 24h
 * (spec 05 §6.2). Runs every 15 minutes. Reserved inventory is released
 * when payment had been selected.
 */
class ExpireAbandonedCheckouts implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Execute the job.
     */
    public function handle(CheckoutService $checkoutService): void
    {
        Checkout::query()
            ->whereNotIn('status', [CheckoutStatus::Completed->value, CheckoutStatus::Expired->value])
            ->where(function ($query): void {
                $query->where('expires_at', '<', now())
                    ->orWhere('updated_at', '<', now()->subHours(24));
            })
            ->chunkById(100, function ($checkouts) use ($checkoutService): void {
                foreach ($checkouts as $checkout) {
                    $checkoutService->expireCheckout($checkout);
                }
            });
    }
}
