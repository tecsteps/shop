<?php

namespace App\Jobs;

use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class CleanupAbandonedCarts implements ShouldQueue
{
    use Queueable;

    public function handle(CheckoutService $checkouts): void
    {
        $cutoff = now()->subDays(14);
        $cartIds = Cart::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('updated_at', '<=', $cutoff)
            ->pluck('id');

        if ($cartIds->isEmpty()) {
            return;
        }

        Checkout::withoutGlobalScopes()
            ->whereIn('cart_id', $cartIds)
            ->whereNotIn('status', ['completed', 'expired'])
            ->orderBy('id')
            ->get()
            ->each(fn (Checkout $checkout) => $checkouts->expire($checkout));

        DB::table('carts')->whereIn('id', $cartIds)->where('status', 'active')->update([
            'status' => 'abandoned',
            'updated_at' => now(),
        ]);
    }
}
