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
    private const CARD_SUCCESS = '4242424242424242';

    private const CARD_DECLINED = '4000000000000002';

    private const CARD_INSUFFICIENT = '4000000000009995';

    /**
     * @param  array<string, mixed>  $details
     */
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        $totals = $checkout->totals_json ?? [];
        $amount = (int) ($totals['total'] ?? 0);
        $currency = (string) ($totals['currency'] ?? 'USD');

        return match ($method) {
            PaymentMethod::CreditCard => $this->chargeCreditCard($details, $amount, $currency),
            PaymentMethod::Paypal => new PaymentResult(
                status: PaymentStatus::Captured,
                providerPaymentId: $this->generateId(),
                amount: $amount,
                currency: $currency,
            ),
            PaymentMethod::BankTransfer => new PaymentResult(
                status: PaymentStatus::Pending,
                providerPaymentId: $this->generateId(),
                amount: $amount,
                currency: $currency,
            ),
        };
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        return new RefundResult(
            status: RefundStatus::Processed,
            providerRefundId: 'mock_ref_'.Str::random(12),
            amount: $amount,
        );
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function chargeCreditCard(array $details, int $amount, string $currency): PaymentResult
    {
        $cardNumber = preg_replace('/\s+/', '', (string) ($details['card_number'] ?? self::CARD_SUCCESS));

        return match ($cardNumber) {
            self::CARD_DECLINED => new PaymentResult(
                status: PaymentStatus::Failed,
                providerPaymentId: null,
                amount: $amount,
                currency: $currency,
                errorMessage: 'card_declined',
            ),
            self::CARD_INSUFFICIENT => new PaymentResult(
                status: PaymentStatus::Failed,
                providerPaymentId: null,
                amount: $amount,
                currency: $currency,
                errorMessage: 'insufficient_funds',
            ),
            default => new PaymentResult(
                status: PaymentStatus::Captured,
                providerPaymentId: $this->generateId(),
                amount: $amount,
                currency: $currency,
            ),
        };
    }

    private function generateId(): string
    {
        return 'mock_'.Str::random(12);
    }
}
