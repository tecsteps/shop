<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Exceptions\PaymentFailedException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShippingRate;
use DomainException;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        private readonly PricingEngine $pricingEngine,
        private readonly InventoryService $inventoryService,
        private readonly OrderService $orderService,
        private readonly PaymentProvider $paymentProvider,
    ) {}

    public function start(Cart $cart): Checkout
    {
        return DB::transaction(function () use ($cart): Checkout {
            $checkout = new Checkout;
            $checkout->store_id = $cart->store_id;
            $checkout->cart_id = $cart->id;
            $checkout->customer_id = $cart->customer_id;
            $checkout->status = CheckoutStatus::Started->value;
            $checkout->save();

            return $checkout;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        $this->assertTransitionAllowed($checkout, [CheckoutStatus::Started, CheckoutStatus::Addressed]);

        $checkout->email = $data['email'] ?? $checkout->email;
        $shipping = $data['shipping_address'] ?? [];
        $billing = $data['billing_address'] ?? $shipping;

        $checkout->shipping_address_json = $shipping;
        $checkout->billing_address_json = $billing;
        $checkout->status = CheckoutStatus::Addressed->value;
        $checkout->save();

        return $this->recalculate($checkout);
    }

    public function setShippingMethod(Checkout $checkout, int $shippingRateId): Checkout
    {
        $this->assertTransitionAllowed($checkout, [
            CheckoutStatus::Addressed,
            CheckoutStatus::ShippingSelected,
        ]);

        $rate = ShippingRate::query()->findOrFail($shippingRateId);

        $checkout->shipping_method_id = $rate->id;
        $checkout->status = CheckoutStatus::ShippingSelected->value;
        $checkout->save();

        return $this->recalculate($checkout);
    }

    public function selectPaymentMethod(Checkout $checkout, string $method): Checkout
    {
        $allowedStates = [CheckoutStatus::ShippingSelected, CheckoutStatus::PaymentSelected];

        $cart = $checkout->cart()->with('lines.variant')->first();
        $requiresShipping = $cart !== null && $cart->lines->contains(
            fn ($line): bool => $line->variant !== null && (bool) $line->variant->requires_shipping
        );

        if (! $requiresShipping) {
            $allowedStates[] = CheckoutStatus::Addressed;
        }

        $this->assertTransitionAllowed($checkout, $allowedStates);

        $allowed = ['credit_card', 'paypal', 'bank_transfer'];
        if (! in_array($method, $allowed, true)) {
            throw new DomainException("Invalid payment method: {$method}");
        }

        return DB::transaction(function () use ($checkout, $method): Checkout {
            $checkout->payment_method = $method;
            $checkout->status = CheckoutStatus::PaymentSelected->value;
            $checkout->expires_at = now()->addHours(24);
            $checkout->save();

            $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();
            if ($cart !== null) {
                foreach ($cart->lines as $line) {
                    $inventoryItem = $line->variant?->inventoryItem;
                    if ($inventoryItem !== null) {
                        $this->inventoryService->reserve($inventoryItem, (int) $line->quantity);
                    }
                }
            }

            return $checkout;
        });
    }

    public function applyDiscount(Checkout $checkout, string $code): Checkout
    {
        $checkout->discount_code = $code;
        $checkout->save();

        return $this->recalculate($checkout);
    }

    public function recalculate(Checkout $checkout): Checkout
    {
        $result = $this->pricingEngine->calculate($checkout);
        $checkout->totals_json = $result->toArray();
        $checkout->save();

        return $checkout;
    }

    public function expire(Checkout $checkout): void
    {
        DB::transaction(function () use ($checkout): void {
            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();
                if ($cart !== null) {
                    foreach ($cart->lines as $line) {
                        $inventoryItem = $line->variant?->inventoryItem;
                        if ($inventoryItem !== null) {
                            $this->inventoryService->release($inventoryItem, (int) $line->quantity);
                        }
                    }
                }
            }

            $checkout->status = CheckoutStatus::Expired->value;
            $checkout->save();
        });
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public function complete(Checkout $checkout, array $details = []): Checkout
    {
        $this->assertTransitionAllowed($checkout, [CheckoutStatus::PaymentSelected]);

        $methodString = $checkout->payment_method;
        if ($methodString === null) {
            throw new DomainException('Checkout has no payment method selected.');
        }
        $method = PaymentMethod::from((string) $methodString);

        $result = $this->paymentProvider->charge($checkout, $method, $details);

        if ($result->failed()) {
            $this->releaseReservedInventory($checkout);
            throw new PaymentFailedException($result->errorMessage ?? 'Payment failed.');
        }

        return DB::transaction(function () use ($checkout, $method, $result): Checkout {
            $order = $this->orderService->createFromCheckout($checkout);

            /** @var Payment $payment */
            $payment = Payment::create([
                'order_id' => $order->id,
                'provider' => 'mock',
                'method' => $method->value,
                'provider_payment_id' => $result->providerPaymentId,
                'status' => $result->status->value,
                'amount' => $result->amount,
                'currency' => $result->currency,
                'raw_json_encrypted' => null,
            ]);

            if ($result->successful()) {
                $order->update([
                    'financial_status' => FinancialStatus::Paid->value,
                    'status' => OrderStatus::Paid->value,
                ]);

                $this->commitReservedInventory($order);

                OrderPaid::dispatch($order->fresh() ?? $order);
            } elseif ($result->pending()) {
                $payment->update(['status' => PaymentStatus::Pending->value]);
            }

            return $checkout->fresh() ?? $checkout;
        });
    }

    private function releaseReservedInventory(Checkout $checkout): void
    {
        $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();
        if ($cart === null) {
            return;
        }

        foreach ($cart->lines as $line) {
            $inventoryItem = $line->variant?->inventoryItem;
            if ($inventoryItem !== null) {
                $this->inventoryService->release($inventoryItem, (int) $line->quantity);
            }
        }
    }

    private function commitReservedInventory(Order $order): void
    {
        $order->loadMissing('lines');
        foreach ($order->lines as $line) {
            if ($line->variant_id === null) {
                continue;
            }
            $item = InventoryItem::withoutGlobalScopes()
                ->where('variant_id', $line->variant_id)
                ->first();
            if ($item !== null) {
                $this->inventoryService->commit($item, (int) $line->quantity);
            }
        }
    }

    /**
     * @param  array<int, CheckoutStatus>  $allowed
     */
    private function assertTransitionAllowed(Checkout $checkout, array $allowed): void
    {
        $current = $checkout->status instanceof CheckoutStatus
            ? $checkout->status
            : CheckoutStatus::from((string) $checkout->status);

        if (! in_array($current, $allowed, true)) {
            throw new DomainException(
                "Invalid checkout transition from {$current->value}."
            );
        }
    }
}
