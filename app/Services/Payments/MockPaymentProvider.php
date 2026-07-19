<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;
use Illuminate\Support\Str;

/**
 * Mock PSP (spec 05 §10): simulates card/PayPal/bank transfer payments fully
 * in-process. No external API calls, no webhooks, no redirects.
 *
 * Magic card numbers:
 *  - 4242 4242 4242 4242  success (captured)
 *  - 4000 0000 0000 0002  card_declined
 *  - 4000 0000 0000 9995  insufficient_funds
 * Any other number succeeds. Expiry, CVC and holder are ignored.
 */
class MockPaymentProvider implements PaymentProvider
{
    private const string CARD_DECLINED = '4000000000000002';

    private const string CARD_INSUFFICIENT_FUNDS = '4000000000009995';

    /**
     * Charge the checkout according to the payment method.
     *
     * @param  array<string, mixed>  $details
     */
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        return match ($method) {
            PaymentMethod::CreditCard => $this->chargeCard($details),
            PaymentMethod::Paypal => PaymentResult::captured($this->referenceId()),
            PaymentMethod::BankTransfer => PaymentResult::pending($this->referenceId()),
        };
    }

    /**
     * Mock refund: always succeeds.
     */
    public function refund(Payment $payment, int $amount): RefundResult
    {
        return new RefundResult(
            success: true,
            providerRefundId: 'mock_refund_'.Str::random(16),
            status: 'processed',
        );
    }

    /**
     * Evaluate the magic card number (spaces are stripped first).
     *
     * @param  array<string, mixed>  $details
     */
    private function chargeCard(array $details): PaymentResult
    {
        $number = str_replace(' ', '', (string) ($details['card_number'] ?? ''));

        return match ($number) {
            self::CARD_DECLINED => PaymentResult::failed('card_declined', 'Your card was declined.'),
            self::CARD_INSUFFICIENT_FUNDS => PaymentResult::failed('insufficient_funds', 'Your card has insufficient funds.'),
            default => PaymentResult::captured($this->referenceId()),
        };
    }

    /**
     * Generate a mock reference ID.
     */
    private function referenceId(): string
    {
        return 'mock_'.Str::random(16);
    }
}
