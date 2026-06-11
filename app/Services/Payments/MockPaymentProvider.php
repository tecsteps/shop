<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProvider
{
    public const string CARD_SUCCESS = '4242424242424242';

    public const string CARD_DECLINED = '4000000000000002';

    public const string CARD_INSUFFICIENT_FUNDS = '4000000000009995';

    /**
     * Process the payment entirely in-process: no external API calls, no
     * webhooks, no redirects (spec 05 section 10).
     *
     * @param  array<string, mixed>  $details
     */
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        return match ($method) {
            PaymentMethod::CreditCard => $this->chargeCreditCard($details),
            PaymentMethod::Paypal => PaymentResult::captured($this->referenceId(), [
                'provider' => 'mock',
                'method' => 'paypal',
                'outcome' => 'captured',
            ]),
            PaymentMethod::BankTransfer => PaymentResult::pending($this->referenceId(), [
                'provider' => 'mock',
                'method' => 'bank_transfer',
                'outcome' => 'pending',
            ]),
        };
    }

    /**
     * Mock refunds always succeed.
     */
    public function refund(Payment $payment, int $amount): RefundResult
    {
        return RefundResult::processed('mock_re_'.Str::lower(Str::random(16)), [
            'provider' => 'mock',
            'payment_reference' => $payment->provider_payment_id,
            'amount' => $amount,
            'outcome' => 'processed',
        ]);
    }

    /**
     * Inspect the magic card number to determine the outcome (spec 05
     * section 10.3). Expiry, CVC, and cardholder name are ignored.
     *
     * @param  array<string, mixed>  $details
     */
    protected function chargeCreditCard(array $details): PaymentResult
    {
        $cardNumber = preg_replace('/\D/', '', (string) ($details['card_number'] ?? ''));

        return match ($cardNumber) {
            self::CARD_DECLINED => PaymentResult::failed('card_declined', [
                'provider' => 'mock',
                'method' => 'credit_card',
                'outcome' => 'declined',
            ]),
            self::CARD_INSUFFICIENT_FUNDS => PaymentResult::failed('insufficient_funds', [
                'provider' => 'mock',
                'method' => 'credit_card',
                'outcome' => 'declined',
            ]),
            default => PaymentResult::captured($this->referenceId(), [
                'provider' => 'mock',
                'method' => 'credit_card',
                'outcome' => 'captured',
                'card_last4' => substr($cardNumber, -4),
            ]),
        };
    }

    /**
     * Generate a mock payment reference ID.
     */
    protected function referenceId(): string
    {
        return 'mock_'.Str::lower(Str::random(16));
    }
}
