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
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        $reference = 'mock_'.Str::random(24);

        if ($method === PaymentMethod::BankTransfer) {
            return new PaymentResult(true, $reference, PaymentStatus::Pending, raw: ['method' => $method->value]);
        }

        if ($method === PaymentMethod::Paypal) {
            return new PaymentResult(true, $reference, PaymentStatus::Captured, raw: ['method' => $method->value]);
        }

        $cardNumber = preg_replace('/\D/', '', (string) ($details['card_number'] ?? ''));

        return match ($cardNumber) {
            '4000000000000002' => new PaymentResult(false, $reference, PaymentStatus::Failed, 'card_declined', 'The card was declined.'),
            '4000000000009995' => new PaymentResult(false, $reference, PaymentStatus::Failed, 'insufficient_funds', 'The card has insufficient funds.'),
            default => new PaymentResult(true, $reference, PaymentStatus::Captured, raw: ['method' => $method->value]),
        };
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        if ($amount <= 0 || $amount > $payment->amount) {
            return new RefundResult(false, '', RefundStatus::Failed, 'invalid_refund_amount');
        }

        return new RefundResult(true, 'mock_refund_'.Str::random(24), RefundStatus::Processed);
    }
}
