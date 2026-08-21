<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentDeclinedException;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private readonly PaymentProvider $provider, private readonly InventoryService $inventory, private readonly OrderService $orders, private readonly PricingEngine $pricing) {}

    public function pay(Checkout $checkout, PaymentMethod $method, array $details = []): ?Order
    {
        $declined = null;
        $order = DB::transaction(function () use ($checkout, $method, $details, &$declined): ?Order {
            $existing = Order::withoutGlobalScopes()->where('checkout_id', $checkout->getKey())->first();

            if ($existing !== null) {
                return $existing->load(['lines', 'payments']);
            }

            if ($checkout->status !== CheckoutStatus::PaymentSelected) {
                throw new \LogicException('Checkout must have a selected payment method before payment.');
            }

            $checkout->update(['payment_method' => $method]);
            $this->pricing->calculate($checkout->refresh());
            $checkout->load('cart.lines.variant.inventory');

            $result = $this->provider->charge($checkout, $method, $details);

            if ($result->status === PaymentStatus::Failed) {
                foreach ($checkout->cart->lines as $line) {
                    if ($line->variant->inventory !== null) {
                        $this->inventory->release($line->variant->inventory, $line->quantity);
                    }
                }

                $checkout->update(['status' => CheckoutStatus::ShippingSelected]);
                $declined = $result;

                return null;
            }

            $order = $this->orders->createFromCheckout($checkout, $result);
            Payment::create(['order_id' => $order->getKey(), 'provider' => 'mock', 'provider_payment_id' => $result->reference, 'method' => $method, 'status' => $result->status, 'amount' => $order->total_amount, 'currency' => $order->currency, 'raw_json' => ['reference' => $result->reference, 'message' => $result->message]]);

            $discountIds = collect($checkout->totals_json['discount_allocations'] ?? [])
                ->flatMap(fn (array $allocations): array => $allocations)
                ->pluck('discount_id')
                ->filter()
                ->unique();

            if ($discountIds->isNotEmpty()) {
                Discount::withoutGlobalScopes()->where('store_id', $checkout->store_id)->whereIn('id', $discountIds)->increment('usage_count');
            }

            return $order->refresh()->load(['lines', 'payments']);
        });

        if ($declined !== null) {
            throw new PaymentDeclinedException($declined->errorCode ?? 'payment_failed', $declined->message ?: 'Payment failed.');
        }

        return $order;
    }
}
