<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProvider
{
    private const CARD_DECLINE = '4000000000000002';

    private const CARD_INSUFFICIENT_FUNDS = '4000000000009995';

    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function charge(Checkout $checkout, PaymentMethod $method, array $paymentMethodData): PaymentResult
    {
        $referenceId = 'mock_'.Str::random(16);

        if ($method === PaymentMethod::BankTransfer) {
            return new PaymentResult(
                success: true,
                status: PaymentStatus::Pending,
                providerPaymentId: $referenceId,
            );
        }

        if ($method === PaymentMethod::CreditCard) {
            /** @var string $rawCardNumber */
            $rawCardNumber = $paymentMethodData['card_number'] ?? '';
            $cardNumber = str_replace(' ', '', (string) $rawCardNumber);

            if ($cardNumber === self::CARD_DECLINE) {
                return new PaymentResult(
                    success: false,
                    status: PaymentStatus::Failed,
                    providerPaymentId: $referenceId,
                    errorCode: 'card_declined',
                    errorMessage: 'The card was declined.',
                );
            }

            if ($cardNumber === self::CARD_INSUFFICIENT_FUNDS) {
                return new PaymentResult(
                    success: false,
                    status: PaymentStatus::Failed,
                    providerPaymentId: $referenceId,
                    errorCode: 'insufficient_funds',
                    errorMessage: 'Insufficient funds.',
                );
            }
        }

        Log::channel('json')->info('Payment charged', [
            'checkout_id' => $checkout->id,
            'method' => $method->value,
            'reference_id' => $referenceId,
            'status' => 'captured',
        ]);

        return new PaymentResult(
            success: true,
            status: PaymentStatus::Captured,
            providerPaymentId: $referenceId,
        );
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        $refundId = 'mock_refund_'.Str::random(16);

        Log::channel('json')->info('Payment refunded', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'amount' => $amount,
            'refund_id' => $refundId,
        ]);

        return new RefundResult(
            success: true,
            providerRefundId: $refundId,
        );
    }
}
