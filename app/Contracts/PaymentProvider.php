<?php

namespace App\Contracts;

use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;

interface PaymentProvider
{
    /**
     * Charge a checkout with the given payment method data.
     *
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function charge(Checkout $checkout, array $paymentMethodData): PaymentResult;

    /**
     * Refund a payment for the given amount.
     */
    public function refund(Payment $payment, int $amount): RefundResult;
}
