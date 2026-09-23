<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use Illuminate\Support\Str;

class MockPaymentProvider
{
    /** @return array{id: string, status: string, message: string} */
    public function charge(PaymentMethod $method, ?string $cardNumber = null): array
    {
        if ($method === PaymentMethod::BankTransfer) {
            return ['id' => 'mock_'.Str::random(20), 'status' => 'pending', 'message' => 'Bank transfer payment is awaiting confirmation.'];
        }

        $normalizedCard = preg_replace('/\D+/', '', (string) $cardNumber);

        if ($method === PaymentMethod::CreditCard && $normalizedCard === '4000000000000002') {
            return ['id' => 'mock_'.Str::random(20), 'status' => 'failed', 'message' => 'Your card was declined.'];
        }

        if ($method === PaymentMethod::CreditCard && $normalizedCard === '4000000000009995') {
            return ['id' => 'mock_'.Str::random(20), 'status' => 'failed', 'message' => 'Your card has insufficient funds.'];
        }

        return ['id' => 'mock_'.Str::random(20), 'status' => 'captured', 'message' => 'Payment completed successfully.'];
    }
}
