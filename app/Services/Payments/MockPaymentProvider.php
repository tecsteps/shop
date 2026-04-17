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
    public const CARD_SUCCESS = '4242424242424242';

    public const CARD_DECLINE = '4000000000000002';

    public const CARD_INSUFFICIENT_FUNDS = '4000000000009995';

    public const CARD_3DS = '5555555555554444';

    /**
     * @param  array<string, mixed>  $details
     */
    public function authorize(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        $providerId = 'mock_auth_'.Str::random(16);

        return match ($method) {
            PaymentMethod::CreditCard => $this->authorizeCreditCard($providerId, $details),
            PaymentMethod::Paypal => PaymentResult::captured($providerId, ['method' => 'paypal']),
            PaymentMethod::BankTransfer => PaymentResult::pending($providerId, ['method' => 'bank_transfer']),
        };
    }

    public function capture(Payment $payment): PaymentResult
    {
        return PaymentResult::captured(
            (string) $payment->provider_payment_id,
            ['capture_id' => 'mock_cap_'.Str::random(12)],
        );
    }

    public function void(Payment $payment): PaymentResult
    {
        return new PaymentResult(
            success: true,
            status: \App\Enums\PaymentStatus::Failed,
            providerPaymentId: $payment->provider_payment_id,
            raw: ['voided' => true],
        );
    }

    public function refund(Payment $payment, int $amount): RefundResult
    {
        if ($amount <= 0) {
            return RefundResult::failed('invalid_amount');
        }

        return RefundResult::succeeded(
            'mock_refund_'.Str::random(16),
            ['amount' => $amount],
        );
    }

    /**
     * @param  array<string, mixed>  $details
     */
    protected function authorizeCreditCard(string $providerId, array $details): PaymentResult
    {
        $card = preg_replace('/\D+/', '', (string) ($details['card_number'] ?? ''));

        return match ($card) {
            self::CARD_DECLINE => PaymentResult::declined('card_declined', ['card' => $card]),
            self::CARD_INSUFFICIENT_FUNDS => PaymentResult::declined('insufficient_funds', ['card' => $card]),
            self::CARD_3DS, self::CARD_SUCCESS, '' => PaymentResult::captured($providerId, ['card' => $card]),
            default => PaymentResult::captured($providerId, ['card' => $card]),
        };
    }
}
