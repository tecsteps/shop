<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Models\Checkout;
use App\Models\Payment;
use App\Services\Payment\MockPaymentProvider;

class PaymentService
{
    public function __construct(private readonly ?PaymentProvider $provider = null) {}

    /**
     * @param  array<string, mixed>  $paymentMethodData
     * @return array<string, mixed>
     */
    public function charge(Checkout $checkout, array $paymentMethodData = []): array
    {
        return $this->provider()->charge($checkout, $paymentMethodData);
    }

    /**
     * @return array<string, mixed>
     */
    public function refund(Payment $payment, int $amount): array
    {
        return $this->provider()->refund($payment, $amount);
    }

    private function provider(): PaymentProvider
    {
        return $this->provider ?? app(MockPaymentProvider::class);
    }
}
