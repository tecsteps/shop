<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;

class CustomerService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function register(Store $store, array $data): Customer
    {
        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $data['password'],
            'marketing_opt_in' => $data['marketing_opt_in'] ?? false,
        ]);

        Auth::guard('customer')->login($customer);

        return $customer;
    }
}
