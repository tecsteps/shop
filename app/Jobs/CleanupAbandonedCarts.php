<?php

namespace App\Jobs;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Services\CheckoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Mark carts inactive for longer than the abandonment threshold as
 * abandoned (spec 05 §4.5). Runs daily. The threshold defaults to 14 days
 * and is configurable per store via store_settings
 * (settings_json.cart_abandon_days). Active checkouts of abandoned carts
 * are expired first so their reserved inventory is released.
 */
class CleanupAbandonedCarts implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Execute the job.
     */
    public function handle(CheckoutService $checkoutService): void
    {
        Cart::query()
            ->where('status', CartStatus::Active->value)
            ->where('updated_at', '<', now()->subDay())
            ->with('store.settings')
            ->chunkById(100, function ($carts) use ($checkoutService): void {
                foreach ($carts as $cart) {
                    $thresholdDays = (int) ($cart->store->settings?->settings_json['cart_abandon_days'] ?? 14);

                    if ($cart->updated_at->gte(now()->subDays($thresholdDays))) {
                        continue;
                    }

                    foreach ($cart->checkouts()->whereNotIn('status', ['completed', 'expired'])->get() as $checkout) {
                        $checkoutService->expireCheckout($checkout);
                    }

                    $cart->update(['status' => CartStatus::Abandoned]);
                }
            });
    }
}
