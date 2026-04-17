<?php

namespace App\Observers;

use App\Events\CustomerCreated;
use App\Models\Customer;

class CustomerObserver
{
    public function created(Customer $customer): void
    {
        CustomerCreated::dispatch($customer);
    }
}
