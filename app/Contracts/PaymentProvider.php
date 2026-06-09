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
     * Process a payment for the checkout in-process (mock PSP, spec 05
     * section 10). Credit card outcomes depend on magic card numbers,
     * PayPal always captures, bank transfer returns a pending result.
     *
     * @param  array<string, mixed>  $details  Method-specific details, e.g. card number
     */
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult;

    /**
     * Issue a refund against a captured payment.
     */
    public function refund(Payment $payment, int $amount): RefundResult;
}
