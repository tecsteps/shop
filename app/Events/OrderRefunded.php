<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

class OrderRefunded
{
    use Dispatchable;

    public function __construct(public Order $order) {}
}
