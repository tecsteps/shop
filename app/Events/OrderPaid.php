<?php

namespace App\Events;

use App\Models\Order;

class OrderPaid
{
    public function __construct(public Order $order) {}
}
