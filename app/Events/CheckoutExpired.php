<?php

namespace App\Events;

use App\Models\Checkout;

class CheckoutExpired
{
    public function __construct(public Checkout $checkout) {}
}
