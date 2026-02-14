<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Storefront\StorefrontController;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class AccountController extends StorefrontController
{
    protected function currentCustomerId(): int
    {
        $customerId = Auth::guard('customer')->id();

        if (! is_numeric($customerId)) {
            abort(401, 'Customer not authenticated.');
        }

        return (int) $customerId;
    }

    protected function currentCustomer(Request $request): Customer
    {
        $customer = Customer::query()
            ->whereKey($this->currentCustomerId())
            ->where('store_id', $this->currentStoreId($request))
            ->first();

        if (! $customer instanceof Customer) {
            abort(404, 'Customer not found.');
        }

        return $customer;
    }
}
