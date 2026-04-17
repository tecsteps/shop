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
        /** @var \Illuminate\Database\Eloquent\Collection<int, Checkout> $checkouts */
        $checkouts = Checkout::query()
            ->withoutGlobalScopes()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereNotIn('status', [
                CheckoutStatus::Completed->value,
                CheckoutStatus::Expired->value,
            ])
            ->get();

        foreach ($checkouts as $checkout) {
            $checkoutService->expireCheckout($checkout);
        }
    }
}
