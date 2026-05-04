<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;

class PaymentService
{
    public function __construct(
        private readonly PaymentProvider $provider,
    ) {}

    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function charge(Checkout $checkout, PaymentMethod $method, array $paymentMethodData = []): PaymentResult
    {
        return $this->provider->charge($checkout, $method, $paymentMethodData);
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        return $this->provider->refund($payment, $amount);
    }
}
