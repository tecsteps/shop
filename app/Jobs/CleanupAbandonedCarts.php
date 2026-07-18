<?php

namespace App\Jobs;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Models\Cart;
use App\Services\CheckoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupAbandonedCarts implements ShouldQueue
{
    use Queueable;

    public function handle(CheckoutService $checkoutService): void
    {
        Cart::query()
            ->where('status', CartStatus::Active)
            ->with(['store.settings', 'checkouts'])
            ->chunkById(100, function ($carts) use ($checkoutService): void {
                foreach ($carts as $cart) {
                    $days = (int) ($cart->store->settings?->settings_json['cart_abandon_days'] ?? 14);

                    if ($cart->updated_at->isAfter(now()->subDays($days))) {
                        continue;
                    }

                    foreach ($cart->checkouts as $checkout) {
                        if (! in_array($checkout->status, [CheckoutStatus::Completed, CheckoutStatus::Expired], true)) {
                            $checkoutService->expireCheckout($checkout);
                        }
                    }

                    $cart->update(['status' => CartStatus::Abandoned]);
                }
            });
    }
}
