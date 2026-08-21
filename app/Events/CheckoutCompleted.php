<?php

namespace App\Events;

use App\Models\Checkout;
use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckoutCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Checkout $checkout, public Order $order) {}
}
