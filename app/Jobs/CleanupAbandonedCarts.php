<?php

namespace App\Jobs;

use App\Enums\CheckoutStatus;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CleanupAbandonedCarts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function handle(InventoryService $inventory): void
    {
        Cart::withoutGlobalScopes()->where('status', 'active')->where('updated_at', '<', now()->subDays(14))->each(function (Cart $cart) use ($inventory): void {
            DB::transaction(function () use ($cart, $inventory): void {
                $cart->load('lines.variant.inventory');
                $checkouts = Checkout::withoutGlobalScopes()
                    ->where('cart_id', $cart->getKey())
                    ->where('status', CheckoutStatus::PaymentSelected->value)
                    ->lockForUpdate()
                    ->get();

                foreach ($checkouts as $checkout) {
                    foreach ($cart->lines as $line) {
                        if ($line->variant?->inventory !== null) {
                            $inventory->release($line->variant->inventory, $line->quantity);
                        }
                    }

                    $checkout->update(['status' => CheckoutStatus::Expired]);
                }

                $cart->update(['status' => 'abandoned']);
            });
        });
    }
}
