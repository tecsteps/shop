<?php

namespace App\Contracts;

use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Models\Payment;
use App\Services\Payments\PaymentResult;
use App\Services\Payments\RefundResult;

interface PaymentProvider
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult;

    public function refund(Payment $payment, int $amount): RefundResult;
}
