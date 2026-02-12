<?php

namespace App\Jobs;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ExpireAbandonedCheckouts implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(CheckoutService $checkoutService): void
    {
        $expiredStatuses = [
            CheckoutStatus::Started,
            CheckoutStatus::Addressed,
            CheckoutStatus::ShippingSelected,
            CheckoutStatus::PaymentPending,
        ];

        $checkouts = Checkout::whereIn('status', $expiredStatuses)
            ->where('updated_at', '<', now()->subHours(24))
            ->cursor();

        foreach ($checkouts as $checkout) {
            $checkoutService->expireCheckout($checkout);
        }
    }
}
