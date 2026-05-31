<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when every line of an order has been fulfilled.
 */
class OrderFulfilled
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}
