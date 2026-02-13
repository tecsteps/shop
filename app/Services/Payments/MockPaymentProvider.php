<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProvider
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        $providerPaymentId = 'mock_'.Str::random(16);

        return match ($method) {
            PaymentMethod::CreditCard => $this->chargeCreditCard($details, $providerPaymentId),
            PaymentMethod::Paypal => new PaymentResult(
                success: true,
                status: 'captured',
                errorCode: null,
                providerPaymentId: $providerPaymentId,
                rawResponse: ['provider' => 'mock', 'method' => 'paypal'],
            ),
            PaymentMethod::BankTransfer => new PaymentResult(
                success: true,
                status: 'pending',
                errorCode: null,
                providerPaymentId: $providerPaymentId,
                rawResponse: ['provider' => 'mock', 'method' => 'bank_transfer'],
            ),
        };
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        return new RefundResult(
            success: true,
            status: 'processed',
            providerRefundId: 'mock_ref_'.Str::random(16),
            rawResponse: ['provider' => 'mock', 'refunded_amount' => $amount],
        );
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function chargeCreditCard(array $details, string $providerPaymentId): PaymentResult
    {
        $cardNumber = $details['card_number'] ?? '';

        return match ($cardNumber) {
            '4000000000000002' => new PaymentResult(
                success: false,
                status: 'failed',
                errorCode: 'card_declined',
                providerPaymentId: $providerPaymentId,
                rawResponse: ['provider' => 'mock', 'decline_code' => 'card_declined'],
            ),
            '4000000000009995' => new PaymentResult(
                success: false,
                status: 'failed',
                errorCode: 'insufficient_funds',
                providerPaymentId: $providerPaymentId,
                rawResponse: ['provider' => 'mock', 'decline_code' => 'insufficient_funds'],
            ),
            default => new PaymentResult(
                success: true,
                status: 'captured',
                errorCode: null,
                providerPaymentId: $providerPaymentId,
                rawResponse: ['provider' => 'mock', 'method' => 'credit_card'],
            ),
        };
    }
}
