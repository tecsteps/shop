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
        $checkouts = Checkout::withoutGlobalScopes()
            ->whereNotIn('status', [
                CheckoutStatus::Completed->value,
                CheckoutStatus::Expired->value,
            ])
            ->where('updated_at', '<', now()->subHours(24)->toIso8601String())
            ->get();

        foreach ($checkouts as $checkout) {
            $checkoutService->expireCheckout($checkout);
        }
    }
}
