<?php

namespace App\Events;

use App\Models\Order;

class OrderCancelled
{
    public function __construct(public Order $order) {}
}
