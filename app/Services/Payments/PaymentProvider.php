<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentProvider
{
    public function charge(Order $order, array $paymentDetails): PaymentResult;

    public function refund(Order $order, int $amount, ?int $paymentId = null): PaymentResult;
}
