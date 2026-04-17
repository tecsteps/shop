<?php

namespace App\Contracts;

use App\Models\Checkout;
use App\Models\Payment;
use App\Services\Payment\PaymentResult;
use App\Services\Payment\RefundResult;

interface PaymentProvider
{
    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function charge(Checkout $checkout, array $paymentMethodData): PaymentResult;

    public function refund(Payment $payment, int $amount): RefundResult;
}
