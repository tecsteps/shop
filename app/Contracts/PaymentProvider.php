<?php

namespace App\Contracts;

use App\Models\Checkout;
use App\Models\Payment;

interface PaymentProvider
{
    /**
     * @param  array<string, mixed>  $paymentMethodData
     * @return array<string, mixed>
     */
    public function charge(Checkout $checkout, array $paymentMethodData = []): array;

    /**
     * @return array<string, mixed>
     */
    public function refund(Payment $payment, int $amount): array;
}
