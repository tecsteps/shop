<?php

namespace App\Jobs;

use App\Jobs\Concerns\RestoresCurrentStore;
use App\Models\Cart;
use App\Models\Store;
use App\Services\CheckoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CleanupAbandonedCarts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use RestoresCurrentStore;

    public int $tries = 3;

    public int $timeout = 300;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function handle(CheckoutService $checkouts): void
    {
        $this->restoringCurrentStore(function () use ($checkouts): void {
            Cart::withoutGlobalScopes()->where('status', 'active')->where('updated_at', '<', now()->subDays(14))
                ->with(['checkouts' => fn ($query) => $query->withoutGlobalScopes()])
                ->chunkById(100, function ($carts) use ($checkouts): void {
                    foreach ($carts as $cart) {
                        app()->instance('current_store', Store::query()->findOrFail($cart->store_id));
                        foreach ($cart->checkouts->whereNotIn('status', ['completed', 'expired']) as $checkout) {
                            $checkouts->expireCheckout($checkout);
                        }
                        $cart->update(['status' => 'abandoned']);
                    }
                });
        });
    }
}
