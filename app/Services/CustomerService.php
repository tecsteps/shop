<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Store;

/**
 * Customer account operations.
 *
 * This phase provides the guest-linking primitive used during order creation
 * (spec section 12.4). Registration and account management are layered on in the
 * customer-accounts phase.
 */
class CustomerService
{
    /**
     * Find an existing customer by email within a store, or create a passwordless
     * guest customer that can later claim the account.
     */
    public function findOrCreateGuest(Store $store, string $email): Customer
    {
        return Customer::query()
            ->where('store_id', $store->id)
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
            ->first()
            ?? Customer::create([
                'store_id' => $store->id,
                'email' => $email,
                'password_hash' => null,
                'name' => null,
                'marketing_opt_in' => false,
            ]);
    }
}
