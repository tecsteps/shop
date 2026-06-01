<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a refund is processed against an order.
 */
class OrderRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}
