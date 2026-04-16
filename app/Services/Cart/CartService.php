<?php

namespace App\Services\Cart;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function createForStore(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::create([
            'store_id' => $store->id,
            'customer_id' => $customer?->id,
            'currency' => $store->default_currency,
            'cart_version' => 1,
            'status' => CartStatus::Active->value,
        ]);
    }

    public function addLine(Cart $cart, ProductVariant $variant, int $quantity): CartLine
    {
        if ($quantity < 1) {
            $quantity = 1;
        }

        return DB::transaction(function () use ($cart, $variant, $quantity) {
            $line = CartLine::where('cart_id', $cart->id)
                ->where('variant_id', $variant->id)
                ->first();

            if ($line) {
                $line->quantity += $quantity;
            } else {
                $line = new CartLine([
                    'cart_id' => $cart->id,
                    'variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'unit_price_amount' => $variant->price_amount,
                ]);
            }

            $line->unit_price_amount = $variant->price_amount;
            $line->line_subtotal_amount = $line->quantity * $variant->price_amount;
            $line->line_total_amount = $line->line_subtotal_amount - $line->line_discount_amount;
            $line->save();

            $cart->touch();

            return $line;
        });
    }

    public function updateLineQuantity(CartLine $line, int $quantity): ?CartLine
    {
        if ($quantity <= 0) {
            $line->delete();

            return null;
        }

        $line->quantity = $quantity;
        $line->line_subtotal_amount = $quantity * $line->unit_price_amount;
        $line->line_total_amount = $line->line_subtotal_amount - $line->line_discount_amount;
        $line->save();

        $line->cart->touch();

        return $line;
    }

    public function removeLine(CartLine $line): void
    {
        $cart = $line->cart;
        $line->delete();
        $cart->touch();
    }

    public function clear(Cart $cart): void
    {
        $cart->lines()->delete();
        $cart->touch();
    }

    public function attachCustomer(Cart $cart, Customer $customer): Cart
    {
        $cart->customer_id = $customer->id;
        $cart->save();

        return $cart;
    }
}
