<?php

namespace App\Events;

use App\Models\Checkout;

class CheckoutShippingSelected
{
    public function __construct(public Checkout $checkout) {}
}
