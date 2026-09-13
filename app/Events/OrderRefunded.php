<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Refund;

class OrderRefunded
{
    public function __construct(
        public Order $order,
        public Refund $refund,
    ) {}
}
