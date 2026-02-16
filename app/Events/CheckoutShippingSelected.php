<?php

namespace App\Events;

use App\Models\Checkout;
use Illuminate\Foundation\Events\Dispatchable;

class CheckoutShippingSelected
{
    use Dispatchable;

    public function __construct(public Checkout $checkout) {}
}
