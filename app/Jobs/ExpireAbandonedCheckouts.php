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
            /** @var CheckoutStatus|string $checkoutStatus */
            $checkoutStatus = $checkout->status;
            $isPaymentSelected = $checkoutStatus instanceof CheckoutStatus
                ? $checkoutStatus === CheckoutStatus::PaymentSelected
                : $checkoutStatus === CheckoutStatus::PaymentSelected->value;

            if ($isPaymentSelected) {
                $checkout->load('cart.lines.variant.inventoryItem');

                $cart = $checkout->cart;
                if ($cart) {
                    foreach ($cart->lines as $line) {
                        if ($line->variant && $line->variant->inventoryItem) {
                            $inventoryService->release($line->variant->inventoryItem, $line->quantity);
                        }
                    }
                }
            }

            $checkout->update([
                'status' => CheckoutStatus::Expired,
            ]);
        }
    }
}
