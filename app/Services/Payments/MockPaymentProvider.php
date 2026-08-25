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
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        return match ($method) {
            PaymentMethod::CreditCard => $this->chargeCard($details),
            PaymentMethod::Paypal => new PaymentResult(true, 'mock_'.Str::random(16), 'captured'),
            PaymentMethod::BankTransfer => new PaymentResult(true, 'mock_'.Str::random(16), 'pending'),
        };
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        return new RefundResult(true, 're_'.Str::random(16), 'processed');
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function chargeCard(array $details): PaymentResult
    {
        $card = preg_replace('/\s+/', '', (string) ($details['card_number'] ?? ''));

        return match ($card) {
            '4000000000000002' => new PaymentResult(false, null, 'failed', 'card_declined', 'Your card was declined.'),
            '4000000000009995' => new PaymentResult(false, null, 'failed', 'insufficient_funds', 'Your card has insufficient funds.'),
            default => new PaymentResult(true, 'mock_'.Str::random(16), 'captured'),
        };
    }
}
