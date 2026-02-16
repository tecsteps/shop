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
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        $mockId = 'mock_'.Str::random(24);

        return match ($method) {
            PaymentMethod::CreditCard => $this->chargeCreditCard($details, $mockId),
            PaymentMethod::Paypal => new PaymentResult(
                success: true,
                status: 'captured',
                providerPaymentId: $mockId,
            ),
            PaymentMethod::BankTransfer => new PaymentResult(
                success: true,
                status: 'pending',
                providerPaymentId: $mockId,
            ),
        };
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        return new RefundResult(
            success: true,
            providerRefundId: 'mock_'.Str::random(24),
        );
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function chargeCreditCard(array $details, string $mockId): PaymentResult
    {
        $cardNumber = $details['card_number'] ?? '';

        if ($cardNumber === '4000000000000002') {
            return new PaymentResult(
                success: false,
                status: 'failed',
                errorCode: 'card_declined',
                errorMessage: 'Your card was declined.',
            );
        }

        if ($cardNumber === '4000000000009995') {
            return new PaymentResult(
                success: false,
                status: 'failed',
                errorCode: 'insufficient_funds',
                errorMessage: 'Your card has insufficient funds.',
            );
        }

        return new PaymentResult(
            success: true,
            status: 'captured',
            providerPaymentId: $mockId,
        );
    }
}
