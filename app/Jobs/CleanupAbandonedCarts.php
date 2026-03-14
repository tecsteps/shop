<?php

namespace App\Jobs;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CleanupAbandonedCarts implements ShouldQueue
{
    use Queueable;

    public function handle(CheckoutService $checkoutService): void
    {
        $carts = Cart::query()
            ->withoutGlobalScopes()
            ->where('status', CartStatus::Active->value)
            ->where('updated_at', '<', now()->subDays(14))
            ->get();

        foreach ($carts as $cart) {
            try {
                $activeCheckouts = Checkout::query()
                    ->withoutGlobalScopes()
                    ->where('cart_id', $cart->id)
                    ->whereNotIn('status', [
                        CheckoutStatus::Completed->value,
                        CheckoutStatus::Expired->value,
                    ])
                    ->get();

                foreach ($activeCheckouts as $checkout) {
                    $checkoutService->expireCheckout($checkout);
                }

                $cart->update(['status' => CartStatus::Abandoned]);
            } catch (\Throwable $e) {
                Log::error('Failed to abandon cart', [
                    'cart_id' => $cart->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($carts->isNotEmpty()) {
            Log::info('Cleaned up abandoned carts', ['count' => $carts->count()]);
        }
    }
}
