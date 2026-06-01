<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;

/**
 * Orchestrates payment processing by delegating to the bound
 * {@see PaymentProvider} (the mock PSP in this application).
 */
class PaymentService
{
    public function __construct(private readonly PaymentProvider $provider) {}

    /**
     * Charge a checkout's selected payment method.
     *
     * @param  array<string, mixed>  $details
     */
    public function charge(Checkout $checkout, array $details = []): PaymentResult
    {
        $method = $checkout->payment_method ?? PaymentMethod::CreditCard;

        return $this->provider->charge($checkout, $method, $details);
    }

    /**
     * Refund an amount against a captured payment.
     */
    public function refund(Payment $payment, int $amount): RefundResult
    {
        return $this->provider->refund($payment, $amount);
    }
}
