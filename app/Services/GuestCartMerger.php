<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

/**
 * Merges a guest session cart into the customer's cart on login,
 * per spec 05 section 4.1. Duplicate variants keep the higher quantity.
 */
class GuestCartMerger
{
    public function mergeFromSession(Customer $customer): void
    {
        $guestCartId = session()->pull('cart_id');

        if ($guestCartId === null) {
            return;
        }

        $guestCart = Cart::query()
            ->where('id', $guestCartId)
            ->where('status', CartStatus::Active)
            ->whereNull('customer_id')
            ->first();

        if ($guestCart === null) {
            return;
        }

        DB::transaction(function () use ($guestCart, $customer): void {
            $customerCart = Cart::query()
                ->where('customer_id', $customer->getKey())
                ->where('status', CartStatus::Active)
                ->first();

            if ($customerCart === null) {
                $guestCart->customer_id = $customer->getKey();
                $guestCart->save();

                session()->forget('cart_id');

                return;
            }

            foreach ($guestCart->cartLines as $line) {
                $existing = $customerCart->cartLines()->where('variant_id', $line->variant_id)->first();

                if ($existing === null) {
                    $line->cart_id = $customerCart->getKey();
                    $line->save();

                    continue;
                }

                $existing->quantity = max($existing->quantity, $line->quantity);
                $existing->line_subtotal_amount = $existing->unit_price_amount * $existing->quantity;
                $existing->line_discount_amount = 0;
                $existing->line_total_amount = $existing->line_subtotal_amount;
                $existing->save();
            }

            $guestCart->status = CartStatus::Abandoned;
            $guestCart->save();

            $customerCart->increment('cart_version');
        });
    }
}
