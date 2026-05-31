<?php

namespace App\Jobs;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

/**
 * Expires checkouts idle for longer than the configured window (24h), releasing
 * any reserved inventory. Runs every 15 minutes.
 */
class ExpireAbandonedCheckouts implements ShouldQueue
{
    use Queueable;

    public function handle(CheckoutService $checkouts): void
    {
        $now = Carbon::now();
        $cutoff = $now->copy()->subHours((int) config('shop.checkout_expiry_hours', 24));

        // Expire when the explicit expiry has passed, or (for checkouts that
        // never reached payment and so have no expiry) when idle past the window.
        Checkout::query()
            ->withoutGlobalScopes()
            ->whereNotIn('status', [CheckoutStatus::Completed->value, CheckoutStatus::Expired->value])
            ->where(function ($query) use ($now, $cutoff): void {
                $query->where('expires_at', '<', $now)
                    ->orWhere(fn ($q) => $q->whereNull('expires_at')->where('updated_at', '<', $cutoff));
            })
            ->each(function (Checkout $checkout) use ($checkouts): void {
                $checkout->setRelation('store', $checkout->store()->withoutGlobalScopes()->first());
                $checkouts->expireCheckout($checkout);
            });
    }
}
