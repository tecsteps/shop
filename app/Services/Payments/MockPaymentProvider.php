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
    public const CARD_SUCCESS = '4242424242424242';

    public const CARD_DECLINE = '4000000000000002';

    public const CARD_INSUFFICIENT = '4000000000009995';

    public function charge(Checkout $checkout, PaymentMethod $method, array $details = []): PaymentResult
    {
        return match ($method) {
            PaymentMethod::CreditCard => $this->chargeCreditCard($details),
            PaymentMethod::Paypal => $this->successResult(),
            PaymentMethod::BankTransfer => $this->bankTransferResult(),
        };
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        return new RefundResult(
            success: true,
            status: RefundStatus::Processed,
            providerRefundId: 'mock_refund_'.Str::random(16),
        );
    }

    /**
     * @param  array<string, mixed>  $details
     */
    protected function chargeCreditCard(array $details): PaymentResult
    {
        $card = preg_replace('/\s+/', '', (string) ($details['card_number'] ?? ''));

        return match ($card) {
            self::CARD_DECLINE => new PaymentResult(false, PaymentStatus::Failed, errorCode: 'card_declined'),
            self::CARD_INSUFFICIENT => new PaymentResult(false, PaymentStatus::Failed, errorCode: 'insufficient_funds'),
            default => $this->successResult(),
        };
    }

    protected function successResult(): PaymentResult
    {
        return new PaymentResult(
            success: true,
            status: PaymentStatus::Captured,
            providerPaymentId: 'mock_'.Str::random(16),
        );
    }

    protected function bankTransferResult(): PaymentResult
    {
        return new PaymentResult(
            success: true,
            status: PaymentStatus::Pending,
            providerPaymentId: 'mock_bank_'.Str::random(16),
        );
    }
}
