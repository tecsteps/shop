<?php

namespace App\Contracts;

use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;

/**
 * Processes payments in-process. The platform ships a mock implementation
 * ({@see \App\Services\Payment\MockPaymentProvider}); no external gateway,
 * webhooks, or redirects are involved.
 */
interface PaymentProvider
{
    /**
     * Charge a checkout. `$details` carries payment-method specific fields such
     * as a credit card number (for magic-number test scenarios).
     *
     * @param  array<string, mixed>  $details
     */
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult;

    /**
     * Issue a refund of `$amount` minor units against a captured payment.
     */
    public function refund(Payment $payment, int $amount): RefundResult;
}
