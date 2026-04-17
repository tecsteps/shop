<?php

namespace App\Events;

use App\Models\Customer;
use Illuminate\Foundation\Events\Dispatchable;

class CustomerCreated
{
    use Dispatchable;

    public function __construct(public readonly Customer $customer) {}
}
