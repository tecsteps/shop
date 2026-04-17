<?php

namespace App\Support;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;

class CartSession
{
    public static function getOrCreate(Store $store): Cart
    {
        $cartId = session('cart_id');
        if ($cartId !== null) {
            $cart = Cart::withoutGlobalScopes()->find($cartId);
            if ($cart !== null && $cart->status === CartStatus::Active) {
                return $cart;
            }
        }

        $customer = Auth::guard('customer')->user();
        $cart = app(CartService::class)->create($store, $customer, session()->getId());
        session(['cart_id' => $cart->id]);

        return $cart;
    }

    public static function current(): ?Cart
    {
        $id = session('cart_id');
        if ($id === null) {
            return null;
        }

        $cart = Cart::withoutGlobalScopes()->find($id);
        if ($cart === null || $cart->status !== CartStatus::Active) {
            return null;
        }

        return $cart;
    }

    public static function clear(): void
    {
        session()->forget('cart_id');
    }
}
