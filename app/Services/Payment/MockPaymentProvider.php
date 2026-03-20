<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProvider
{
    private const MAGIC_CARDS = [
        '4000000000000002' => 'card_declined',
        '4000000000009995' => 'insufficient_funds',
    ];

    public function charge(Checkout $checkout, string $paymentMethod, array $details = []): PaymentResult
    {
        $referenceId = 'mock_'.Str::random(16);

        if ($paymentMethod === 'bank_transfer') {
            return new PaymentResult(
                success: true,
                status: 'pending',
                providerPaymentId: $referenceId,
            );
        }

        if ($paymentMethod === 'credit_card') {
            $cardNumber = str_replace(' ', '', $details['card_number'] ?? '');

            if (isset(self::MAGIC_CARDS[$cardNumber])) {
                return new PaymentResult(
                    success: false,
                    status: 'failed',
                    providerPaymentId: $referenceId,
                    error: self::MAGIC_CARDS[$cardNumber],
                );
            }
        }

        return new PaymentResult(
            success: true,
            status: 'captured',
            providerPaymentId: $referenceId,
        );
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        return new RefundResult(
            success: true,
            providerRefundId: 'mock_refund_'.Str::random(16),
        );
    }
}
