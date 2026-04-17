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
        $activeStatuses = [
            CheckoutStatus::Started,
            CheckoutStatus::Addressed,
            CheckoutStatus::ShippingSelected,
            CheckoutStatus::PaymentSelected,
        ];

        Checkout::query()
            ->withoutGlobalScopes()
            ->whereIn('status', $activeStatuses)
            ->where('updated_at', '<', now()->subHours(24))
            ->each(function (Checkout $checkout) use ($checkoutService) {
                $checkoutService->expireCheckout($checkout);
            });
    }
}
