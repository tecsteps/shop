<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrderCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Order $order, public readonly string $reason = '') {}
}
