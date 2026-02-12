<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCreated;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\ShippingRate;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CheckoutService
{
    public function __construct(
        private InventoryService $inventoryService,
        private PricingEngine $pricingEngine,
        private ShippingCalculator $shippingCalculator,
        private PaymentProvider $paymentProvider,
    ) {}

    public function createFromCart(Cart $cart, Store $store): Checkout
    {
        return Checkout::create([
            'store_id' => $store->id,
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started,
            'discount_code' => $cart->discount_code,
        ]);
    }

    /**
     * @param  array<string, mixed>  $addressData
     */
    public function setAddress(Checkout $checkout, array $addressData): Checkout
    {
        $checkout->update([
            'email' => $addressData['email'],
            'shipping_address_json' => $addressData['shipping_address'],
            'billing_address_json' => $addressData['billing_address'] ?? $addressData['shipping_address'],
            'status' => CheckoutStatus::Addressed,
        ]);

        $this->recalculateTotals($checkout);

        return $checkout->fresh();
    }

    public function setShippingMethod(Checkout $checkout, ?int $shippingRateId): Checkout
    {
        $cart = $checkout->cart()->with('lines')->first();

        // Skip if no items require shipping
        $requiresShipping = $cart->lines->contains(fn ($line) => $line->requires_shipping);

        if (! $requiresShipping) {
            $checkout->update([
                'selected_shipping_rate_id' => null,
                'shipping_method_name' => null,
                'shipping_amount' => 0,
                'status' => CheckoutStatus::ShippingSelected,
            ]);

            $this->recalculateTotals($checkout);

            return $checkout->fresh();
        }

        if (! $shippingRateId) {
            throw new InvalidArgumentException('Shipping rate is required for physical items.');
        }

        $rate = ShippingRate::findOrFail($shippingRateId);
        $zone = $rate->zone;
        $address = $checkout->shipping_address_json ?? [];
        $store = $checkout->store ?? Store::find($checkout->store_id);

        $matchingZone = $this->shippingCalculator->getMatchingZone($address, $store);

        if (! $matchingZone || $matchingZone->id !== $zone->id) {
            throw new InvalidArgumentException('Selected shipping rate does not apply to the shipping address.');
        }

        $amount = $this->shippingCalculator->calculateRateAmount($rate, $cart);

        $checkout->update([
            'selected_shipping_rate_id' => $rate->id,
            'shipping_method_name' => $rate->name,
            'shipping_amount' => $amount ?? 0,
            'status' => CheckoutStatus::ShippingSelected,
        ]);

        $this->recalculateTotals($checkout);

        return $checkout->fresh();
    }

    public function selectPaymentMethod(Checkout $checkout, string $paymentMethod): Checkout
    {
        $method = PaymentMethod::from($paymentMethod);

        $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();

        foreach ($cart->lines as $line) {
            if ($line->variant?->inventoryItem) {
                $this->inventoryService->reserve($line->variant->inventoryItem, $line->quantity);
            }
        }

        $checkout->update([
            'payment_method' => $method,
            'status' => CheckoutStatus::PaymentPending,
            'expires_at' => now()->addHours(24),
        ]);

        return $checkout->fresh();
    }

    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function completeCheckout(Checkout $checkout, array $paymentMethodData): Order
    {
        return DB::transaction(function () use ($checkout, $paymentMethodData): Order {
            // Idempotency check
            $existingOrder = Order::where('checkout_id', $checkout->id)->first();

            if ($existingOrder) {
                return $existingOrder;
            }

            $cart = $checkout->cart()->with('lines.variant.product', 'lines.variant.inventoryItem')->first();
            $store = Store::find($checkout->store_id);

            // Charge payment
            $paymentResult = $this->paymentProvider->charge($checkout, $paymentMethodData);

            if (! $paymentResult->success) {
                // Release reserved inventory on failure
                foreach ($cart->lines as $line) {
                    if ($line->variant?->inventoryItem) {
                        $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                    }
                }

                throw new InvalidArgumentException("Payment failed: {$paymentResult->errorCode}");
            }

            // Determine statuses based on payment method
            $isBankTransfer = $checkout->payment_method === PaymentMethod::BankTransfer
                || $checkout->payment_method?->value === 'bank_transfer';

            if ($isBankTransfer) {
                $orderStatus = OrderStatus::Pending;
                $financialStatus = FinancialStatus::Pending;
                $paymentStatus = PaymentStatus::Pending;
                $commitInventory = false;
            } else {
                $orderStatus = OrderStatus::Paid;
                $financialStatus = FinancialStatus::Paid;
                $paymentStatus = PaymentStatus::Captured;
                $commitInventory = true;
            }

            // Generate order number
            $orderNumber = $this->generateOrderNumber($store);

            $totals = $checkout->totals_json ?? [];

            // Create order
            $order = Order::create([
                'store_id' => $store->id,
                'customer_id' => $checkout->customer_id,
                'checkout_id' => $checkout->id,
                'order_number' => $orderNumber,
                'status' => $orderStatus,
                'financial_status' => $financialStatus,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'email' => $checkout->email,
                'currency' => $cart->currency,
                'subtotal_amount' => $totals['subtotal'] ?? $cart->lines->sum('line_subtotal_amount'),
                'discount_amount' => $totals['discount'] ?? 0,
                'shipping_amount' => $totals['shipping'] ?? $checkout->shipping_amount ?? 0,
                'tax_amount' => $totals['tax_total'] ?? 0,
                'total_amount' => $totals['total'] ?? $cart->lines->sum('line_total_amount'),
                'shipping_address_json' => $checkout->shipping_address_json,
                'billing_address_json' => $checkout->billing_address_json,
                'payment_method' => $checkout->payment_method,
                'discount_code' => $checkout->discount_code,
                'placed_at' => now(),
            ]);

            // Create order lines
            foreach ($cart->lines as $line) {
                $order->lines()->create([
                    'product_id' => $line->variant?->product_id,
                    'variant_id' => $line->variant_id,
                    'title_snapshot' => $line->product_title.($line->variant_title ? ' - '.$line->variant_title : ''),
                    'variant_title_snapshot' => $line->variant_title,
                    'sku_snapshot' => $line->sku ?? $line->variant?->sku,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'discount_amount' => $line->line_discount_amount,
                    'tax_amount' => 0,
                    'total_amount' => $line->line_total_amount,
                    'requires_shipping' => $line->requires_shipping,
                    'fulfilled_quantity' => 0,
                ]);
            }

            // Create payment record
            $order->payments()->create([
                'method' => $checkout->payment_method,
                'status' => $paymentStatus,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'reference' => $paymentResult->referenceId,
                'captured_at' => $commitInventory ? now() : null,
            ]);

            // Commit or keep reserved inventory
            if ($commitInventory) {
                foreach ($cart->lines as $line) {
                    if ($line->variant?->inventoryItem) {
                        $this->inventoryService->commit($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            // Increment discount usage
            if ($checkout->discount_code) {
                $discount = \App\Models\Discount::where('store_id', $store->id)
                    ->whereRaw('LOWER(code) = ?', [strtolower($checkout->discount_code)])
                    ->first();

                if ($discount) {
                    $discount->increment('times_used');
                }
            }

            // Mark cart as converted
            $cart->update(['status' => CartStatus::Converted]);

            // Mark checkout as completed
            $checkout->update([
                'status' => CheckoutStatus::Completed,
                'completed_at' => now(),
            ]);

            // Auto-fulfill digital orders
            if ($commitInventory) {
                $allDigital = $order->lines->every(fn ($line) => ! $line->requires_shipping);

                if ($allDigital) {
                    $this->autoFulfillDigitalOrder($order);
                }
            }

            OrderCreated::dispatch($order);

            return $order;
        });
    }

    public function expireCheckout(Checkout $checkout): void
    {
        if ($checkout->status === CheckoutStatus::PaymentPending) {
            $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();

            foreach ($cart->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                }
            }
        }

        $checkout->update(['status' => CheckoutStatus::Expired]);
    }

    private function recalculateTotals(Checkout $checkout): void
    {
        $cart = $checkout->cart()->with('lines.variant.product')->first();
        $store = Store::find($checkout->store_id);

        $result = $this->pricingEngine->calculate($cart, $store, $checkout);

        $checkout->update([
            'totals_json' => $result->toArray(),
        ]);
    }

    private function generateOrderNumber(Store $store): string
    {
        $maxNumber = Order::where('store_id', $store->id)
            ->selectRaw('MAX(CAST(order_number AS INTEGER)) as max_num')
            ->value('max_num');

        $nextNumber = ($maxNumber ?? 1000) + 1;

        return (string) $nextNumber;
    }

    private function autoFulfillDigitalOrder(Order $order): void
    {
        $fulfillment = $order->fulfillments()->create([
            'status' => FulfillmentShipmentStatus::Delivered,
            'shipped_at' => now(),
            'delivered_at' => now(),
        ]);

        foreach ($order->lines as $line) {
            $fulfillment->lines()->create([
                'order_line_id' => $line->id,
                'quantity' => $line->quantity,
            ]);
        }

        $order->update([
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'status' => OrderStatus::Fulfilled,
        ]);
    }
}
