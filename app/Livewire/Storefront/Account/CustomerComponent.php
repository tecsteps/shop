<?php

namespace App\Livewire\Storefront\Account;

use App\Livewire\Storefront\StorefrontComponent;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

abstract class CustomerComponent extends StorefrontComponent
{
    protected function authenticatedCustomer(): Customer
    {
        /** @var Customer|null $customer */
        $customer = Auth::guard('customer')->user();
        abort_unless($customer, 401);
        abort_unless((int) $customer->store_id === (int) $this->currentStore()->getKey(), 403);

        return $customer;
    }
}
