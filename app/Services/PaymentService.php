<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentFailedException;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly PaymentProvider $provider,
        private readonly InventoryService $inventory,
    ) {}

    /**
     * Authorize (and capture for card/paypal) via the provider. Returns the result
     * without creating any Payment row; caller persists via recordPayment().
     *
     * @param  array<string, mixed>  $details
     */
    public function authorize(Checkout $checkout, PaymentMethod $method, array $details = []): PaymentResult
    {
        $result = $this->provider->authorize($checkout, $method, $details);

        if (! $result->success) {
            $this->releaseReservations($checkout);

            throw new PaymentFailedException($result->errorCode ?? 'payment_failed');
        }

        return $result;
    }

    public function recordPayment(Order $order, PaymentMethod $method, PaymentResult $result): Payment
    {
        return DB::transaction(function () use ($order, $method, $result): Payment {
            $existing = Payment::query()
                ->where('provider', 'mock')
                ->where('provider_payment_id', $result->providerPaymentId)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return Payment::query()->create([
                'order_id' => $order->getKey(),
                'provider' => 'mock',
                'method' => $method->value,
                'provider_payment_id' => $result->providerPaymentId,
                'status' => $result->status->value,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'raw_json_encrypted' => $result->raw,
                'created_at' => now(),
            ]);
        });
    }

    public function refund(Payment $payment, int $amount): PaymentResult
    {
        $result = $this->provider->refund($payment, $amount);

        if (! $result->success) {
            throw new PaymentFailedException($result->errorCode ?? 'refund_failed');
        }

        if ($amount >= $payment->amount) {
            $payment->status = PaymentStatus::Refunded;
            $payment->save();
        }

        return new PaymentResult(
            success: true,
            status: $payment->status,
            providerPaymentId: $payment->provider_payment_id,
            raw: ['refund_id' => $result->providerRefundId],
        );
    }

    public function releaseReservations(Checkout $checkout): void
    {
        foreach ($checkout->cart->lines()->with('variant')->get() as $line) {
            if ($line->variant && $line->variant->requires_shipping) {
                $this->inventory->release($line->variant, (int) $line->quantity);
            }
        }
    }
}
