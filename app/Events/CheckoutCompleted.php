<?php

namespace App\Events;

use App\Models\Checkout;
use App\Models\Order;

class CheckoutCompleted
{
    public function __construct(
        public Checkout $checkout,
        public Order $order,
    ) {}
}
