<?php

namespace App\Jobs;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireAbandonedCheckouts implements ShouldQueue
{
    use Queueable;

    public function handle(InventoryService $inventoryService): void
    {
        $checkouts = Checkout::withoutGlobalScopes()
            ->whereNotIn('status', [
                CheckoutStatus::Completed->value,
                CheckoutStatus::Expired->value,
            ])
            ->where('updated_at', '<', now()->subHours(24))
            ->get();

        foreach ($checkouts as $checkout) {
            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                $checkout->load('cart.lines.variant.inventoryItem');

                foreach ($checkout->cart->lines as $line) {
                    if ($line->variant->inventoryItem) {
                        $inventoryService->release($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            $checkout->update([
                'status' => CheckoutStatus::Expired,
            ]);
        }
    }
}
