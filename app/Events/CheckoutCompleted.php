<?php

namespace App\Events;

use App\Models\Checkout;
use Illuminate\Foundation\Events\Dispatchable;

class CheckoutCompleted
{
    use Dispatchable;

    public function __construct(public Checkout $checkout) {}
}
