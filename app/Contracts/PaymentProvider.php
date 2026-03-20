<?php

namespace App\Contracts;

use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;

interface PaymentProvider
{
    public function charge(Checkout $checkout, string $paymentMethod, array $details = []): PaymentResult;

    public function refund(Payment $payment, int $amount): RefundResult;
}
