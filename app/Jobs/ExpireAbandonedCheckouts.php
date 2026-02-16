<?php

namespace App\Jobs;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireAbandonedCheckouts implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Checkout::withoutGlobalScopes()
            ->whereNotIn('status', [
                CheckoutStatus::Completed->value,
                CheckoutStatus::Expired->value,
            ])
            ->where('expires_at', '<', now())
            ->each(function (Checkout $checkout): void {
                $checkout->update(['status' => CheckoutStatus::Expired]);
            });
    }
}
