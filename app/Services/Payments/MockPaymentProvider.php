<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Models\Payment;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProvider
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        $providerPaymentId = 'mock_'.Str::random(24);

        return match ($method) {
            PaymentMethod::CreditCard => $this->chargeCreditCard($details, $providerPaymentId),
            PaymentMethod::Paypal => new PaymentResult(
                success: true,
                status: 'captured',
                providerPaymentId: $providerPaymentId,
            ),
            PaymentMethod::BankTransfer => new PaymentResult(
                success: true,
                status: 'pending',
                providerPaymentId: $providerPaymentId,
            ),
        };
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        return new RefundResult(
            success: true,
            providerRefundId: 'mock_refund_'.Str::random(24),
        );
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function chargeCreditCard(array $details, string $providerPaymentId): PaymentResult
    {
        $cardNumber = str_replace(' ', '', $details['card_number'] ?? '');

        return match ($cardNumber) {
            '4000000000000002' => new PaymentResult(
                success: false,
                status: 'failed',
                providerPaymentId: $providerPaymentId,
                errorCode: 'card_declined',
                errorMessage: 'Your card was declined.',
            ),
            '4000000000009995' => new PaymentResult(
                success: false,
                status: 'failed',
                providerPaymentId: $providerPaymentId,
                errorCode: 'insufficient_funds',
                errorMessage: 'Your card has insufficient funds.',
            ),
            default => new PaymentResult(
                success: true,
                status: 'captured',
                providerPaymentId: $providerPaymentId,
            ),
        };
    }
}
