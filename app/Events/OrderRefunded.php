<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Refund;
use Illuminate\Foundation\Events\Dispatchable;

class OrderRefunded
{
    use Dispatchable;

    public function __construct(
        public readonly Order $order,
        public readonly Refund $refund,
    ) {}
}
