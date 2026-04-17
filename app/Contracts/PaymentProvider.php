<?php

namespace App\Contracts;

use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;

interface PaymentProvider
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function authorize(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult;

    public function capture(Payment $payment): PaymentResult;

    public function void(Payment $payment): PaymentResult;

    public function refund(Payment $payment, int $amount): RefundResult;
}
