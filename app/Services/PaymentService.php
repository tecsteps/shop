<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Exceptions\PaymentDeclinedException;
use App\Models\Checkout;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly MockPaymentProvider $provider,
        private readonly OrderService $orders,
        private readonly InventoryService $inventory,
    ) {}

    /** @param array<string, mixed> $paymentDetails */
    public function pay(Checkout $checkout, array $paymentDetails = []): Order
    {
        $existing = Order::withoutGlobalScopes()->where('checkout_id', $checkout->id)->first();

        if ($existing) {
            return $existing->load('lines', 'payments');
        }

        if ($checkout->status !== 'payment_selected' || ($checkout->expires_at && $checkout->expires_at->isPast())) {
            abort(422, 'This checkout is not ready for payment.');
        }

        $method = PaymentMethod::from($checkout->payment_method);
        $result = $this->provider->charge($method, $paymentDetails['card_number'] ?? null);

        if ($result['status'] === 'failed') {
            DB::transaction(function () use ($checkout): void {
                $checkout->loadMissing('cart.lines.variant.inventoryItem');

                foreach ($checkout->cart->lines as $line) {
                    if ($line->variant?->inventoryItem) {
                        $this->inventory->release($line->variant->inventoryItem, $line->quantity);
                    }
                }

                $checkout->forceFill(['status' => 'shipping_selected', 'expires_at' => null])->save();
            });

            $codeName = str_contains($result['message'], 'insufficient') ? 'insufficient_funds' : 'payment_declined';
            throw new PaymentDeclinedException($result['message'], $codeName);
        }

        return $this->orders->createFromCheckout($checkout, $result);
    }
}
