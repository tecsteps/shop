<?php

namespace App\Jobs;

use App\Models\Cart;
use App\Services\CheckoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CleanupAbandonedCarts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(CheckoutService $checkouts): void
    {
        Cart::withoutGlobalScopes()->where('status', 'active')->where('updated_at', '<', now()->subDays(14))
            ->with('checkouts')
            ->chunkById(100, function ($carts) use ($checkouts): void {
                foreach ($carts as $cart) {
                    foreach ($cart->checkouts->whereNotIn('status', ['completed', 'expired']) as $checkout) {
                        $checkouts->expireCheckout($checkout);
                    }
                    $cart->update(['status' => 'abandoned']);
                }
            });
    }
}
