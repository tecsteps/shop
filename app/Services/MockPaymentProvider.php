<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProvider
{
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        if ($method === PaymentMethod::BankTransfer) {
            return new PaymentResult(PaymentStatus::Pending, 'mock_'.Str::lower(Str::random(16)), 'Bank transfer instructions generated.');
        }

        if ($method === PaymentMethod::CreditCard) {
            $number = preg_replace('/\D+/', '', (string) ($details['card_number'] ?? ''));

            return match ($number) {
                '4000000000000002' => new PaymentResult(PaymentStatus::Failed, 'mock_'.Str::lower(Str::random(16)), 'Your card was declined.'),
                '4000000000009995' => new PaymentResult(PaymentStatus::Failed, 'mock_'.Str::lower(Str::random(16)), 'Your card has insufficient funds.'),
                default => new PaymentResult(PaymentStatus::Captured, 'mock_'.Str::lower(Str::random(16)), 'Payment captured.'),
            };
        }

        return new PaymentResult(PaymentStatus::Captured, 'mock_'.Str::lower(Str::random(16)), 'Payment captured.');
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        return new RefundResult(true, 'mock_refund_'.Str::lower(Str::random(16)), 'Refund issued.');
    }
}
