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
    private const CARD_SUCCESS = '4242424242424242';

    private const CARD_DECLINE = '4000000000000002';

    private const CARD_INSUFFICIENT = '4000000000009995';

    /**
     * @param  array<string, mixed>  $details
     */
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        $referenceId = 'mock_'.Str::random(20);

        return match ($method) {
            PaymentMethod::CreditCard => $this->chargeCreditCard($details, $referenceId),
            PaymentMethod::Paypal => new PaymentResult(
                success: true,
                status: 'captured',
                providerPaymentId: $referenceId,
            ),
            PaymentMethod::BankTransfer => new PaymentResult(
                success: true,
                status: 'pending',
                providerPaymentId: $referenceId,
            ),
        };
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        return new RefundResult(
            success: true,
            providerRefundId: 'mock_refund_'.Str::random(20),
        );
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function chargeCreditCard(array $details, string $referenceId): PaymentResult
    {
        $cardNumber = str_replace(' ', '', (string) ($details['card_number'] ?? ''));

        if ($cardNumber === self::CARD_DECLINE) {
            return new PaymentResult(
                success: false,
                status: 'failed',
                providerPaymentId: $referenceId,
                errorCode: 'card_declined',
                errorMessage: 'The card was declined.',
            );
        }

        if ($cardNumber === self::CARD_INSUFFICIENT) {
            return new PaymentResult(
                success: false,
                status: 'failed',
                providerPaymentId: $referenceId,
                errorCode: 'insufficient_funds',
                errorMessage: 'The card has insufficient funds.',
            );
        }

        return new PaymentResult(
            success: true,
            status: 'captured',
            providerPaymentId: $referenceId,
        );
    }
}
