<?php

namespace App\Services\Payments;

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
        if ($method === PaymentMethod::CreditCard) {
            $cardNumber = Str::of((string) ($details['card_number'] ?? ''))->replaceMatches('/\D/', '')->toString();

            if ($cardNumber === '4000000000000002') {
                return new PaymentResult(false, PaymentStatus::Failed, errorCode: 'card_declined');
            }

            if ($cardNumber === '4000000000009995') {
                return new PaymentResult(false, PaymentStatus::Failed, errorCode: 'insufficient_funds');
            }
        }

        $status = $method === PaymentMethod::BankTransfer ? PaymentStatus::Pending : PaymentStatus::Captured;
        $reference = 'mock_'.Str::random(24);

        return new PaymentResult(true, $status, $reference, raw: ['provider' => 'mock', 'reference' => $reference, 'status' => $status->value]);
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        $reference = 'mock_refund_'.Str::random(20);

        return new RefundResult(true, $reference, raw: ['provider' => 'mock', 'reference' => $reference, 'amount' => $amount]);
    }
}
