<?php

namespace App\Events;

use App\Models\Checkout;
use Illuminate\Foundation\Events\Dispatchable;

class CheckoutAddressed
{
    use Dispatchable;

    public function __construct(public readonly Checkout $checkout) {}
}
