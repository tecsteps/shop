<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;
use Illuminate\Support\Str;

/**
 * In-process mock payment service provider.
 *
 * No external API calls, webhooks, or redirects. Credit-card outcomes are
 * driven by magic card numbers; PayPal always succeeds; bank transfers return a
 * pending result (deferred capture). All reference ids are prefixed `mock_`.
 */
class MockPaymentProvider implements PaymentProvider
{
    private const CARD_DECLINED = '4000000000000002';

    private const CARD_INSUFFICIENT_FUNDS = '4000000000009995';

    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        return match ($method) {
            PaymentMethod::CreditCard => $this->chargeCard($details),
            PaymentMethod::Paypal => $this->capturedResult(),
            PaymentMethod::BankTransfer => $this->pendingResult(),
        };
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        return new RefundResult(
            success: true,
            providerRefundId: $this->reference(),
            status: RefundStatus::Processed,
            raw: ['amount' => $amount, 'payment' => $payment->provider_payment_id],
        );
    }

    private function chargeCard(array $details): PaymentResult
    {
        $card = preg_replace('/\D/', '', (string) ($details['card_number'] ?? ''));

        return match ($card) {
            self::CARD_DECLINED => $this->failedResult('card_declined', 'The card was declined.'),
            self::CARD_INSUFFICIENT_FUNDS => $this->failedResult('insufficient_funds', 'The card has insufficient funds.'),
            default => $this->capturedResult(),
        };
    }

    private function capturedResult(): PaymentResult
    {
        $reference = $this->reference();

        return new PaymentResult(
            success: true,
            referenceId: $reference,
            status: PaymentStatus::Captured,
            raw: ['reference' => $reference, 'outcome' => 'captured'],
        );
    }

    private function pendingResult(): PaymentResult
    {
        $reference = $this->reference();

        return new PaymentResult(
            success: true,
            referenceId: $reference,
            status: PaymentStatus::Pending,
            raw: ['reference' => $reference, 'outcome' => 'pending'],
        );
    }

    private function failedResult(string $errorCode, string $message): PaymentResult
    {
        return new PaymentResult(
            success: false,
            referenceId: $this->reference(),
            status: PaymentStatus::Failed,
            errorCode: $errorCode,
            errorMessage: $message,
            raw: ['outcome' => 'failed', 'error' => $errorCode],
        );
    }

    private function reference(): string
    {
        return 'mock_'.Str::lower(Str::random(24));
    }
}
