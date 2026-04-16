<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProvider
{
    public const MAGIC_SUCCESS = '4242424242424242';

    public const MAGIC_DECLINED = '4000000000000002';

    public const MAGIC_INSUFFICIENT = '4000000000009995';

    public function charge(Order $order, array $paymentDetails): PaymentResult
    {
        $method = (string) ($paymentDetails['method'] ?? 'credit_card');

        if ($method === 'bank_transfer') {
            return PaymentResult::success('mock_bt_'.Str::random(12), 'authorized', [
                'method' => 'bank_transfer',
                'instructions' => 'Bank transfer pending manual reconciliation.',
            ]);
        }

        if ($method === 'paypal') {
            return PaymentResult::success('mock_pp_'.Str::random(12), 'captured', [
                'method' => 'paypal',
            ]);
        }

        $cardNumber = preg_replace('/\s+/', '', (string) ($paymentDetails['card_number'] ?? self::MAGIC_SUCCESS));

        if ($cardNumber === self::MAGIC_DECLINED) {
            return PaymentResult::failure('card_declined', 'mock_cc_'.Str::random(12), [
                'card_last4' => substr($cardNumber, -4),
            ]);
        }

        if ($cardNumber === self::MAGIC_INSUFFICIENT) {
            return PaymentResult::failure('insufficient_funds', 'mock_cc_'.Str::random(12), [
                'card_last4' => substr($cardNumber, -4),
            ]);
        }

        return PaymentResult::success('mock_cc_'.Str::random(12), 'captured', [
            'method' => 'credit_card',
            'card_last4' => substr($cardNumber, -4),
        ]);
    }

    public function refund(Order $order, int $amount, ?int $paymentId = null): PaymentResult
    {
        return PaymentResult::success('mock_rf_'.Str::random(12), 'succeeded', [
            'order_id' => $order->id,
            'amount' => $amount,
            'payment_id' => $paymentId,
        ]);
    }
}
