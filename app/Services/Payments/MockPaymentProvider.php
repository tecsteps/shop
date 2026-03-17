<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProvider
{
    private const MAGIC_CARDS = [
        '4000000000000002' => ['error' => 'card_declined', 'message' => 'Your card was declined.'],
        '4000000000009995' => ['error' => 'insufficient_funds', 'message' => 'Your card has insufficient funds.'],
    ];

    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function charge(Checkout $checkout, array $paymentMethodData): PaymentResult
    {
        $method = PaymentMethod::from($checkout->payment_method->value);
        $referenceId = 'mock_' . Str::random(24);

        return match ($method) {
            PaymentMethod::CreditCard => $this->chargeCreditCard($paymentMethodData, $referenceId),
            PaymentMethod::Paypal => PaymentResult::success(PaymentStatus::Captured, $referenceId, [
                'provider' => 'mock',
                'method' => 'paypal',
                'reference' => $referenceId,
            ]),
            PaymentMethod::BankTransfer => PaymentResult::success(PaymentStatus::Pending, $referenceId, [
                'provider' => 'mock',
                'method' => 'bank_transfer',
                'reference' => $referenceId,
                'note' => 'Awaiting bank transfer confirmation',
            ]),
        };
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        $refundId = 'mock_refund_' . Str::random(24);

        return RefundResult::success($refundId);
    }

    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    private function chargeCreditCard(array $paymentMethodData, string $referenceId): PaymentResult
    {
        $cardNumber = preg_replace('/\s+/', '', $paymentMethodData['card_number'] ?? '');

        if (isset(self::MAGIC_CARDS[$cardNumber])) {
            $decline = self::MAGIC_CARDS[$cardNumber];

            return PaymentResult::failure($decline['error'], $decline['message'], [
                'provider' => 'mock',
                'method' => 'credit_card',
                'error' => $decline['error'],
            ]);
        }

        return PaymentResult::success(PaymentStatus::Captured, $referenceId, [
            'provider' => 'mock',
            'method' => 'credit_card',
            'reference' => $referenceId,
            'last4' => substr($cardNumber, -4),
        ]);
    }
}
