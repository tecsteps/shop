<?php

namespace App\Services\Payment;

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
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function charge(Checkout $checkout, array $paymentMethodData): PaymentResult
    {
        $referenceId = 'mock_'.Str::uuid()->toString();

        $method = $checkout->payment_method instanceof PaymentMethod
            ? $checkout->payment_method
            : PaymentMethod::from($checkout->payment_method);

        return match ($method) {
            PaymentMethod::CreditCard => $this->chargeCreditCard($paymentMethodData, $referenceId),
            PaymentMethod::Paypal => new PaymentResult(
                success: true,
                referenceId: $referenceId,
                status: 'captured',
            ),
            PaymentMethod::BankTransfer => new PaymentResult(
                success: true,
                referenceId: $referenceId,
                status: 'pending',
            ),
        };
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        return new RefundResult(
            success: true,
            providerRefundId: 'mock_refund_'.Str::uuid()->toString(),
            status: 'processed',
        );
    }

    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    private function chargeCreditCard(array $paymentMethodData, string $referenceId): PaymentResult
    {
        $cardNumber = str_replace([' ', '-'], '', $paymentMethodData['card_number'] ?? '');

        if ($cardNumber === self::CARD_DECLINE) {
            return new PaymentResult(
                success: false,
                referenceId: $referenceId,
                status: 'failed',
                errorCode: 'card_declined',
                errorMessage: 'The card was declined.',
            );
        }

        if ($cardNumber === self::CARD_INSUFFICIENT) {
            return new PaymentResult(
                success: false,
                referenceId: $referenceId,
                status: 'failed',
                errorCode: 'insufficient_funds',
                errorMessage: 'Insufficient funds.',
            );
        }

        return new PaymentResult(
            success: true,
            referenceId: $referenceId,
            status: 'captured',
        );
    }
}
