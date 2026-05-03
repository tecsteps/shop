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
    public const SUCCESS_CARD = '4242424242424242';

    public const DECLINE_CARD = '4000000000000002';

    public const INSUFFICIENT_FUNDS_CARD = '4000000000009995';

    /**
     * @param  array<string, mixed>  $details
     */
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        return match ($method) {
            PaymentMethod::CreditCard => $this->chargeCard((string) ($details['card_number'] ?? '')),
            PaymentMethod::Paypal => PaymentResult::captured($this->reference()),
            PaymentMethod::BankTransfer => PaymentResult::pending($this->reference()),
        };
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        if ($amount < 1 || $amount > $payment->amount) {
            return RefundResult::failed('invalid_refund_amount', 'The refund amount is invalid.');
        }

        return RefundResult::processed('mock_ref_'.Str::random(24));
    }

    private function chargeCard(string $cardNumber): PaymentResult
    {
        $cardNumber = preg_replace('/\D+/', '', $cardNumber) ?? '';

        return match ($cardNumber) {
            self::DECLINE_CARD => PaymentResult::failed('card_declined', 'Your card was declined.'),
            self::INSUFFICIENT_FUNDS_CARD => PaymentResult::failed('insufficient_funds', 'Your card has insufficient funds.'),
            default => PaymentResult::captured($this->reference()),
        };
    }

    private function reference(): string
    {
        return 'mock_'.Str::random(24);
    }
}
