<?php

namespace App\Events;

use App\Models\Checkout;

class CheckoutAddressed
{
    public function __construct(public Checkout $checkout) {}
}
