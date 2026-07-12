<?php

namespace App\Events;

use App\Models\Checkout;
use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CheckoutCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Checkout $checkout, public readonly Order $order) {}
}
