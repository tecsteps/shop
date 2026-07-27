<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Store;

/**
 * Customer account lifecycle (spec 06 §1.2).
 */
class CustomerService
{
    /**
     * Register a customer for the store. Email uniqueness per store is
     * validated by the caller before this runs.
     *
     * @param  array{name: string, email: string, password: string, marketing_opt_in?: bool}  $data
     */
    public function register(Store $store, array $data): Customer
    {
        return Customer::create([
            'store_id' => $store->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $data['password'],
            'marketing_opt_in' => $data['marketing_opt_in'] ?? false,
        ]);
    }
}
