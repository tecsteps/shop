<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Models\Payment;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProvider
{
    /**
     * @param  array<string, mixed>  $paymentMethodData
     * @return array<string, mixed>
     */
    public function charge(Checkout $checkout, array $paymentMethodData = []): array
    {
        $method = $this->resolveMethod($checkout, $paymentMethodData);
        $referenceId = 'mock_'.Str::lower(Str::random(12));

        if ($method === PaymentMethod::CreditCard) {
            $cardNumber = preg_replace('/\D+/', '', (string) ($paymentMethodData['card_number'] ?? ''));

            if ($cardNumber === '4000000000000002') {
                return [
                    'success' => false,
                    'reference_id' => $referenceId,
                    'status' => PaymentStatus::Failed->value,
                    'error_code' => 'card_declined',
                    'error_message' => 'Your card was declined.',
                    'provider' => 'mock',
                    'raw' => ['method' => $method->value, 'decline_reason' => 'card_declined'],
                ];
            }

            if ($cardNumber === '4000000000009995') {
                return [
                    'success' => false,
                    'reference_id' => $referenceId,
                    'status' => PaymentStatus::Failed->value,
                    'error_code' => 'insufficient_funds',
                    'error_message' => 'Your card has insufficient funds.',
                    'provider' => 'mock',
                    'raw' => ['method' => $method->value, 'decline_reason' => 'insufficient_funds'],
                ];
            }

            return [
                'success' => true,
                'reference_id' => $referenceId,
                'status' => PaymentStatus::Captured->value,
                'provider' => 'mock',
                'raw' => ['method' => $method->value, 'card_last4' => substr($cardNumber, -4)],
            ];
        }

        if ($method === PaymentMethod::Paypal) {
            return [
                'success' => true,
                'reference_id' => $referenceId,
                'status' => PaymentStatus::Captured->value,
                'provider' => 'mock',
                'raw' => ['method' => $method->value],
            ];
        }

        return [
            'success' => true,
            'reference_id' => $referenceId,
            'status' => PaymentStatus::Pending->value,
            'provider' => 'mock',
            'raw' => ['method' => $method->value],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function refund(Payment $payment, int $amount): array
    {
        return [
            'success' => true,
            'reference_id' => 'refund_'.Str::lower(Str::random(12)),
            'status' => PaymentStatus::Refunded->value,
            'provider' => 'mock',
            'amount' => $amount,
        ];
    }

    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    private function resolveMethod(Checkout $checkout, array $paymentMethodData): PaymentMethod
    {
        $method = $paymentMethodData['payment_method'] ?? $checkout->payment_method?->value;

        if ($method instanceof PaymentMethod) {
            return $method;
        }

        return PaymentMethod::from((string) $method);
    }
}
