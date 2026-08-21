<?php

namespace App\Jobs;

use App\Enums\CheckoutStatus;
use App\Events\CheckoutExpired;
use App\Models\Checkout;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpireAbandonedCheckouts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function handle(InventoryService $inventory): void
    {
        Checkout::withoutGlobalScopes()->whereNotIn('status', [CheckoutStatus::Completed, CheckoutStatus::Expired])->where('expires_at', '<', now())->with('cart.lines.variant.inventory')->each(function (Checkout $checkout) use ($inventory): void {
            foreach ($checkout->cart->lines as $line) {
                if ($line->variant->inventory !== null && $checkout->status === CheckoutStatus::PaymentSelected) {
                    $inventory->release($line->variant->inventory, $line->quantity);
                }
            }

            $checkout->update(['status' => CheckoutStatus::Expired]);
            CheckoutExpired::dispatch($checkout->refresh());
        });
    }
}
