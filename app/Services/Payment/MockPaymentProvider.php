<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;
use Illuminate\Support\Str;

/**
 * In-process mock PSP per spec 05 section 10. No external calls.
 */
class MockPaymentProvider implements PaymentProvider
{
    public const SUCCESS_CARD = '4242424242424242';

    public const DECLINE_CARD = '4000000000000002';

    public const INSUFFICIENT_FUNDS_CARD = '4000000000009995';

    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        if ($method === PaymentMethod::BankTransfer) {
            return new PaymentResult(
                success: true,
                referenceId: $this->referenceId('mock_'),
                status: 'pending',
            );
        }

        if ($method === PaymentMethod::Paypal) {
            return new PaymentResult(
                success: true,
                referenceId: $this->referenceId('mock_'),
                status: 'captured',
            );
        }

        $cardNumber = preg_replace('/\D+/', '', (string) ($details['card_number'] ?? ''));

        if ($cardNumber === self::DECLINE_CARD) {
            return new PaymentResult(
                success: false,
                referenceId: $this->referenceId('mock_'),
                status: 'failed',
                errorCode: 'card_declined',
                errorMessage: 'Your card was declined.',
            );
        }

        if ($cardNumber === self::INSUFFICIENT_FUNDS_CARD) {
            return new PaymentResult(
                success: false,
                referenceId: $this->referenceId('mock_'),
                status: 'failed',
                errorCode: 'insufficient_funds',
                errorMessage: 'Your card has insufficient funds.',
            );
        }

        return new PaymentResult(
            success: true,
            referenceId: $this->referenceId('mock_'),
            status: 'captured',
        );
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        return new RefundResult(
            success: true,
            providerRefundId: $this->referenceId('mock_rf_'),
            status: 'processed',
        );
    }

    protected function referenceId(string $prefix): string
    {
        return $prefix.Str::random(16);
    }
}
