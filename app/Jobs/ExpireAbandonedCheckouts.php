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
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(CheckoutService $checkoutService): void
    {
        Checkout::query()
            ->whereNotIn('status', [CheckoutStatus::Completed, CheckoutStatus::Expired])
            ->where(fn ($query) => $query->where('expires_at', '<', now())->orWhere('updated_at', '<', now()->subDay()))
            ->lazyById()
            ->each(fn (Checkout $checkout) => $checkoutService->expireCheckout($checkout));
    }
}
