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
            ->whereNotIn('status', [CheckoutStatus::Completed->value, CheckoutStatus::Expired->value])
            ->where(function ($query): void {
                $query
                    ->where('expires_at', '<', now())
                    ->orWhere(function ($query): void {
                        $query
                            ->whereNull('expires_at')
                            ->where('updated_at', '<', now()->subDay());
                    });
            })
            ->orderBy('id')
            ->get()
            ->each(fn (Checkout $checkout): Checkout => $checkouts->expireCheckout($checkout));
    }
}
