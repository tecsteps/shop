<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Store;
use App\Services\Payments\PaymentResult;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    public function createFromCheckout(Checkout $checkout, PaymentResult $paymentResult): Order
    {
        return DB::transaction(function () use ($checkout, $paymentResult) {
            $checkout->load('cart.lines.variant.product');
            $cart = $checkout->cart;
            $totals = $checkout->totals_json;
            $store = Store::findOrFail($checkout->store_id);

            // Determine statuses based on payment method
            $isCaptured = in_array($checkout->payment_method, [PaymentMethod::CreditCard, PaymentMethod::Paypal]);

            $orderStatus = $isCaptured ? OrderStatus::Paid : OrderStatus::Pending;
            $financialStatus = $isCaptured ? FinancialStatus::Paid : FinancialStatus::Pending;
            $paymentStatus = $isCaptured ? PaymentStatus::Captured : PaymentStatus::Pending;

            // Generate order number
            $orderNumber = $this->generateOrderNumber($store);

            // Create order
            $order = Order::create([
                'store_id' => $checkout->store_id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $orderNumber,
                'payment_method' => $checkout->payment_method,
                'status' => $orderStatus,
                'financial_status' => $financialStatus,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'currency' => $cart->currency,
                'subtotal_amount' => $totals['subtotal'] ?? 0,
                'discount_amount' => $totals['discount'] ?? 0,
                'shipping_amount' => $totals['shipping'] ?? 0,
                'tax_amount' => $totals['tax_total'] ?? 0,
                'total_amount' => $totals['total'] ?? 0,
                'email' => $checkout->email,
                'billing_address_json' => $checkout->billing_address_json,
                'shipping_address_json' => $checkout->shipping_address_json,
                'placed_at' => now(),
            ]);

            // Create order lines with snapshots
            $allDigital = true;
            foreach ($cart->lines as $line) {
                $variant = $line->variant;
                $product = $variant->product;

                $subtotal = $line->unit_price_amount * $line->quantity;

                OrderLine::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'title_snapshot' => $product->title,
                    'variant_title_snapshot' => ($variant->title && $variant->title !== 'Default') ? $variant->title : null,
                    'sku_snapshot' => $variant->sku,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'subtotal_amount' => $subtotal,
                    'total_amount' => $line->line_total_amount ?? $subtotal,
                    'requires_shipping' => $variant->requires_shipping,
                ]);

                if ($variant->requires_shipping) {
                    $allDigital = false;
                }
            }

            // Create payment record
            Payment::create([
                'order_id' => $order->id,
                'provider' => 'mock',
                'method' => $checkout->payment_method,
                'provider_payment_id' => $paymentResult->providerPaymentId,
                'status' => $paymentStatus,
                'amount' => $totals['total'] ?? 0,
                'currency' => $cart->currency,
            ]);

            // Commit or keep reserved inventory
            if ($isCaptured) {
                foreach ($cart->lines as $line) {
                    $inventoryItem = $line->variant->inventoryItem;
                    if ($inventoryItem) {
                        $this->inventoryService->commit($inventoryItem, $line->quantity);
                    }
                }
            }

            // Increment discount usage
            if ($checkout->discount_code) {
                Discount::withoutGlobalScopes()
                    ->where('store_id', $checkout->store_id)
                    ->whereRaw('LOWER(code) = ?', [strtolower($checkout->discount_code)])
                    ->increment('usage_count');
            }

            // Mark cart as converted
            $cart->update(['status' => CartStatus::Converted]);

            // Mark checkout as completed
            $checkout->update(['status' => CheckoutStatus::Completed]);

            // Auto-fulfill digital products if payment captured
            if ($isCaptured && $allDigital) {
                $this->autoFulfillDigitalOrder($order);
            }

            OrderCreated::dispatch($order);

            return $order;
        });
    }

    public function generateOrderNumber(Store $store): string
    {
        $maxNumber = Order::where('store_id', $store->id)->max('order_number');

        if ($maxNumber) {
            return (string) ((int) $maxNumber + 1);
        }

        return '1001';
    }

    public function cancel(Order $order, string $reason): void
    {
        if ($order->fulfillment_status !== FulfillmentStatus::Unfulfilled) {
            throw new \InvalidArgumentException('Cannot cancel an order that has been partially or fully fulfilled.');
        }

        DB::transaction(function () use ($order, $reason) {
            $order->load('lines.variant.inventoryItem');

            // Release reserved inventory
            foreach ($order->lines as $line) {
                if ($line->variant && $line->variant->inventoryItem) {
                    $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                }
            }

            // Update payment status if pending
            $order->payments()->where('status', PaymentStatus::Pending)->update([
                'status' => PaymentStatus::Failed,
            ]);

            $order->update([
                'status' => OrderStatus::Cancelled,
                'financial_status' => FinancialStatus::Voided,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);
        });

        OrderCancelled::dispatch($order);
    }

    public function confirmBankTransferPayment(Order $order): void
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer) {
            throw new \InvalidArgumentException('Order is not a bank transfer order.');
        }

        if ($order->financial_status !== FinancialStatus::Pending) {
            throw new \InvalidArgumentException('Order payment is not pending.');
        }

        DB::transaction(function () use ($order) {
            $order->load('lines.variant.inventoryItem');

            // Update payment record
            $order->payments()->where('status', PaymentStatus::Pending)->update([
                'status' => PaymentStatus::Captured,
            ]);

            // Update order status
            $order->update([
                'financial_status' => FinancialStatus::Paid,
                'status' => OrderStatus::Paid,
            ]);

            // Commit reserved inventory
            foreach ($order->lines as $line) {
                if ($line->variant && $line->variant->inventoryItem) {
                    $this->inventoryService->commit($line->variant->inventoryItem, $line->quantity);
                }
            }

            // Auto-fulfill if all digital
            $allDigital = $order->lines->every(
                fn (OrderLine $line) => $line->variant && ! $line->variant->requires_shipping
            );

            if ($allDigital) {
                $this->autoFulfillDigitalOrder($order);
            }
        });

        OrderPaid::dispatch($order);
    }

    private function autoFulfillDigitalOrder(Order $order): void
    {
        $order->load('lines');

        $fulfillment = Fulfillment::create([
            'order_id' => $order->id,
            'status' => FulfillmentShipmentStatus::Delivered,
            'delivered_at' => now(),
        ]);

        foreach ($order->lines as $line) {
            FulfillmentLine::create([
                'fulfillment_id' => $fulfillment->id,
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
