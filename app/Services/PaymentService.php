<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;

/**
 * Orchestrates payment processing (spec 05 §10): delegates charge/refund to
 * the bound PaymentProvider and persists Payment records. The provider
 * response is stored encrypted in raw_json_encrypted (spec 06 §4.3); card
 * numbers are never persisted — only the last four digits.
 */
class PaymentService
{
    public function __construct(private PaymentProvider $provider) {}

    /**
     * Charge the checkout with the given payment details.
     *
     * @param  array<string, mixed>  $details
     */
    public function charge(Checkout $checkout, PaymentMethod $method, array $details): PaymentResult
    {
        return $this->provider->charge($checkout, $method, $details);
    }

    /**
     * Refund (part of) a payment.
     */
    public function refund(Payment $payment, int $amount): RefundResult
    {
        return $this->provider->refund($payment, $amount);
    }

    /**
     * Persist the payment record for a freshly created order.
     *
     * @param  array<string, mixed>  $details  original charge details (sanitized before storage)
     */
    public function recordPayment(Order $order, PaymentMethod $method, PaymentResult $result, array $details = []): Payment
    {
        return $order->payments()->create([
            'provider' => 'mock',
            'method' => $method,
            'provider_payment_id' => $result->referenceId,
            'status' => $result->status === 'pending' ? PaymentStatus::Pending : PaymentStatus::Captured,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'raw_json_encrypted' => $this->sanitizeRawPayload($method, $result, $details),
        ]);
    }

    /**
     * Build the encrypted-at-rest payload, keeping only non-sensitive data.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    private function sanitizeRawPayload(PaymentMethod $method, PaymentResult $result, array $details): array
    {
        $payload = [
            'provider' => 'mock',
            'method' => $method->value,
            'reference_id' => $result->referenceId,
            'status' => $result->status,
        ];

        if ($method === PaymentMethod::CreditCard) {
            $number = str_replace(' ', '', (string) ($details['card_number'] ?? ''));
            $payload['card_last4'] = $number !== '' ? substr($number, -4) : null;
            $payload['card_holder'] = $details['card_holder'] ?? null;
        }

        return $payload;
    }
}
