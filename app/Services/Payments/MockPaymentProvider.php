<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProvider
{
    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function charge(Checkout $checkout, PaymentMethod $method, array $paymentMethodData = []): PaymentResult
    {
        return match ($method) {
            PaymentMethod::CreditCard => $this->chargeCreditCard($paymentMethodData),
            PaymentMethod::Paypal => $this->captured(),
            PaymentMethod::BankTransfer => new PaymentResult(
                success: true,
                status: PaymentStatus::Pending,
                referenceId: $this->reference('bank'),
            ),
        };
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        if ($amount <= 0) {
            return new RefundResult(
                success: false,
                status: RefundStatus::Failed,
                errorCode: 'invalid_refund_amount',
                errorMessage: 'Refund amount must be greater than zero.',
            );
        }

        return new RefundResult(
            success: true,
            status: RefundStatus::Processed,
            referenceId: $this->reference('refund'),
        );
    }

    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    private function chargeCreditCard(array $paymentMethodData): PaymentResult
    {
        $number = preg_replace('/\D+/', '', (string) data_get(
            $paymentMethodData,
            'card_number',
            data_get($paymentMethodData, 'number', data_get($paymentMethodData, 'card.number', '')),
        ));

        return match ($number) {
            '4000000000000002' => new PaymentResult(
                success: false,
                status: PaymentStatus::Failed,
                errorCode: 'card_declined',
                errorMessage: 'The card was declined.',
            ),
            '4000000000009995' => new PaymentResult(
                success: false,
                status: PaymentStatus::Failed,
                errorCode: 'insufficient_funds',
                errorMessage: 'The card has insufficient funds.',
            ),
            default => $this->captured(),
        };
    }

    private function captured(): PaymentResult
    {
        return new PaymentResult(
            success: true,
            status: PaymentStatus::Captured,
            referenceId: $this->reference('payment'),
        );
    }

    private function reference(string $prefix): string
    {
        return 'mock_'.$prefix.'_'.Str::lower(Str::random(16));
    }
}
