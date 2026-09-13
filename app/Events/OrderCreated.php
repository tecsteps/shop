<?php

namespace App\Events;

use App\Models\Order;

class OrderCreated
{
    public function __construct(public Order $order) {}
}
