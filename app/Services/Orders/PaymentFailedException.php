<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\Payment;
use RuntimeException;

class PaymentFailedException extends RuntimeException
{
    public function __construct(string $reason, public readonly Order $order, public readonly ?Payment $payment = null)
    {
        parent::__construct($reason);
    }
}
