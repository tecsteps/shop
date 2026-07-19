<?php

namespace App\Livewire\Storefront\Concerns;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\Customer;
use App\Services\CartService;

/**
 * Merge the guest session cart into the customer's active cart after
 * login or registration (spec 05 §4.1, spec 06 §1.2).
 */
trait MergesGuestCartOnLogin
{
    /**
     * Merge the session's guest cart into the customer's active cart and
     * bind the merged cart to the session.
     */
    protected function mergeGuestCartOnLogin(Customer $customer): void
    {
        $store = app('current_store');
        $cartService = app(CartService::class);

        $guest = $cartService->findForSession($store);

        if ($guest === null || $guest->customer_id === $customer->id) {
            return;
        }

        $customerCart = Cart::query()
            ->where('store_id', $store->id)
            ->where('customer_id', $customer->id)
            ->where('status', CartStatus::Active)
            ->latest('id')
            ->first() ?? $cartService->create($store, $customer);

        $cartService->mergeOnLogin($guest, $customerCart);

        session(['cart_id' => $customerCart->id]);
    }
}
