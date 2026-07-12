<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Refund;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrderRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Order $order, public readonly Refund $refund) {}
}
