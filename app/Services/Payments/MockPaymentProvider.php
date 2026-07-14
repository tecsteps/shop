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
    /** @param array<string, mixed> $details */
    public function charge(Checkout $checkout, PaymentMethod $method, array $details = []): PaymentResult
    {
        $reference = 'mock_'.Str::lower(Str::random(24));
        if ($method === PaymentMethod::BankTransfer) {
            return new PaymentResult(true, $reference, 'pending', raw: ['provider' => 'mock', 'method' => $method->value]);
        }
        if ($method === PaymentMethod::Paypal) {
            return new PaymentResult(true, $reference, 'captured', raw: ['provider' => 'mock', 'method' => $method->value]);
        }

        $number = preg_replace('/\D/', '', (string) ($details['card_number'] ?? $details['number'] ?? ''));
        if ($number === '4000000000000002') {
            return new PaymentResult(false, $reference, 'failed', 'card_declined', 'Your card was declined.');
        }
        if ($number === '4000000000009995') {
            return new PaymentResult(false, $reference, 'failed', 'insufficient_funds', 'Your card has insufficient funds.');
        }

        return new PaymentResult(true, $reference, 'captured', raw: ['provider' => 'mock', 'method' => $method->value]);
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        if ($amount < 1) {
            throw new \InvalidArgumentException('Refund amount must be positive.');
        }

        return new RefundResult(true, 'mock_refund_'.Str::lower(Str::random(20)));
    }
}
