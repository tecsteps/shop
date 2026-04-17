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
    /**
     * Magic card numbers for testing credit card payments.
     *
     * @var array<string, array{success: bool, error_code: string|null, error_message: string|null}>
     */
    private const MAGIC_CARDS = [
        '4242424242424242' => ['success' => true, 'error_code' => null, 'error_message' => null],
        '4000000000000002' => ['success' => false, 'error_code' => 'card_declined', 'error_message' => 'Your card was declined.'],
        '4000000000009995' => ['success' => false, 'error_code' => 'insufficient_funds', 'error_message' => 'Your card has insufficient funds.'],
    ];

    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function charge(Checkout $checkout, array $paymentMethodData): PaymentResult
    {
        $referenceId = 'mock_'.Str::random(24);
        $method = $checkout->payment_method;
        $methodValue = $method instanceof \App\Enums\PaymentMethod ? $method->value : (string) $method;

        if ($methodValue === 'bank_transfer') {
            return new PaymentResult(
                success: true,
                referenceId: $referenceId,
                status: 'pending',
            );
        }

        if ($methodValue === 'paypal') {
            return new PaymentResult(
                success: true,
                referenceId: $referenceId,
                status: 'captured',
            );
        }

        // Credit card - check magic card numbers
        $cardNumber = str_replace(' ', '', $paymentMethodData['card_number'] ?? '');
        $magicResult = self::MAGIC_CARDS[$cardNumber] ?? null;

        if ($magicResult !== null) {
            if (! $magicResult['success']) {
                return new PaymentResult(
                    success: false,
                    referenceId: $referenceId,
                    status: 'failed',
                    errorCode: $magicResult['error_code'],
                    errorMessage: $magicResult['error_message'],
                );
            }

            return new PaymentResult(
                success: true,
                referenceId: $referenceId,
                status: 'captured',
            );
        }

        // Any other card number succeeds
        return new PaymentResult(
            success: true,
            referenceId: $referenceId,
            status: 'captured',
        );
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        $refundId = 'mock_refund_'.Str::random(24);

        return new RefundResult(
            success: true,
            providerRefundId: $refundId,
            status: 'processed',
        );
    }
}
