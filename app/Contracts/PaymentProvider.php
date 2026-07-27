<?php

namespace App\Contracts;

use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;

/**
 * Payment Service Provider contract (spec 05 §10.1). The platform ships with
 * a single in-process implementation: the Mock PSP.
 */
interface PaymentProvider
{
    /**
     * Charge the checkout with the given payment method details.
     *
     * @param  array<string, mixed>  $details
     */
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult;

    /**
     * Refund (part of) a captured payment.
     */
    public function refund(Payment $payment, int $amount): RefundResult;
}
