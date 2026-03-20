<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Models\Payment;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProvider
{
    private const CARD_DECLINE = '4000000000000002';

    private const CARD_INSUFFICIENT_FUNDS = '4000000000009995';

    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function charge(Checkout $checkout, array $paymentMethodData): PaymentResult
    {
        $method = $checkout->payment_method;

        return match ($method) {
            PaymentMethod::CreditCard => $this->chargeCreditCard($checkout, $paymentMethodData),
            PaymentMethod::Paypal => $this->chargePaypal($checkout),
            PaymentMethod::BankTransfer => $this->chargeBankTransfer($checkout),
        };
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        $refundId = 'mock_refund_'.Str::uuid();

        return new RefundResult(
            success: true,
            status: 'processed',
            providerRefundId: $refundId,
            rawResponse: [
                'provider' => 'mock',
                'action' => 'refund',
                'original_payment_id' => $payment->provider_payment_id,
                'refund_id' => $refundId,
                'amount' => $amount,
                'currency' => $payment->currency,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    private function chargeCreditCard(Checkout $checkout, array $paymentMethodData): PaymentResult
    {
        $cardNumber = str_replace(' ', '', $paymentMethodData['card_number'] ?? '');

        if ($cardNumber === self::CARD_DECLINE) {
            return new PaymentResult(
                success: false,
                status: 'failed',
                errorCode: 'card_declined',
                errorMessage: 'The card was declined.',
                rawResponse: ['provider' => 'mock', 'card_number' => $cardNumber],
            );
        }

        if ($cardNumber === self::CARD_INSUFFICIENT_FUNDS) {
            return new PaymentResult(
                success: false,
                status: 'failed',
                errorCode: 'insufficient_funds',
                errorMessage: 'Insufficient funds on the card.',
                rawResponse: ['provider' => 'mock', 'card_number' => $cardNumber],
            );
        }

        $paymentId = 'mock_'.Str::uuid();

        return new PaymentResult(
            success: true,
            status: 'captured',
            providerPaymentId: $paymentId,
            rawResponse: [
                'provider' => 'mock',
                'payment_id' => $paymentId,
                'card_number' => $cardNumber,
                'amount' => $checkout->totals_json['total_amount'] ?? 0,
                'currency' => $checkout->cart?->currency ?? 'USD',
            ],
        );
    }

    private function chargePaypal(Checkout $checkout): PaymentResult
    {
        $paymentId = 'mock_'.Str::uuid();

        return new PaymentResult(
            success: true,
            status: 'captured',
            providerPaymentId: $paymentId,
            rawResponse: [
                'provider' => 'mock',
                'payment_id' => $paymentId,
                'method' => 'paypal',
                'amount' => $checkout->totals_json['total_amount'] ?? 0,
                'currency' => $checkout->cart?->currency ?? 'USD',
            ],
        );
    }

    private function chargeBankTransfer(Checkout $checkout): PaymentResult
    {
        $paymentId = 'mock_'.Str::uuid();

        return new PaymentResult(
            success: true,
            status: 'pending',
            providerPaymentId: $paymentId,
            rawResponse: [
                'provider' => 'mock',
                'payment_id' => $paymentId,
                'method' => 'bank_transfer',
                'bank_name' => 'Mock Bank AG',
                'iban' => 'DE89 3704 0044 0532 0130 00',
                'bic' => 'COBADEFFXXX',
                'amount' => $checkout->totals_json['total_amount'] ?? 0,
                'currency' => $checkout->cart?->currency ?? 'USD',
            ],
        );
    }
}
