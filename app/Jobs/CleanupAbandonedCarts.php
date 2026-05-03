<?php

namespace App\Jobs;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupAbandonedCarts implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(CheckoutService $checkouts): void
    {
        Cart::withoutGlobalScopes()
            ->where('status', CartStatus::Active)
            ->where('updated_at', '<', now()->subDays(14))
            ->orderBy('id')
            ->get()
            ->each(function (Cart $cart) use ($checkouts): void {
                Checkout::withoutGlobalScopes()
                    ->where('cart_id', $cart->getKey())
                    ->whereNotIn('status', [CheckoutStatus::Completed->value, CheckoutStatus::Expired->value])
                    ->get()
                    ->each(fn (Checkout $checkout): Checkout => $checkouts->expireCheckout($checkout));

                $cart->forceFill(['status' => CartStatus::Abandoned])->save();
            });
    }
}
