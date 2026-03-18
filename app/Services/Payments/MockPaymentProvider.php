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
    private const CARD_DECLINE = '4000000000000002';

    private const CARD_INSUFFICIENT_FUNDS = '4000000000009995';

    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        $referenceId = 'mock_'.Str::random(24);

        if ($method === PaymentMethod::BankTransfer) {
            return new PaymentResult(
                success: true,
                status: 'pending',
                referenceId: $referenceId,
            );
        }

        if ($method === PaymentMethod::CreditCard) {
            $cardNumber = str_replace(' ', '', $details['card_number'] ?? '');

            if ($cardNumber === self::CARD_DECLINE) {
                return new PaymentResult(
                    success: false,
                    status: 'failed',
                    errorCode: 'card_declined',
                    errorMessage: 'The card was declined.',
                );
            }

            if ($cardNumber === self::CARD_INSUFFICIENT_FUNDS) {
                return new PaymentResult(
                    success: false,
                    status: 'failed',
                    errorCode: 'insufficient_funds',
                    errorMessage: 'Insufficient funds.',
                );
            }
        }

        return new PaymentResult(
            success: true,
            status: 'captured',
            referenceId: $referenceId,
        );
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        return new RefundResult(
            success: true,
            referenceId: 'mock_refund_'.Str::random(24),
        );
    }
}
