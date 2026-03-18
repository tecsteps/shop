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
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\Store;
use App\ValueObjects\PaymentResult;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    public function createFromCheckout(Checkout $checkout, PaymentResult $paymentResult): Order
    {
        return DB::transaction(function () use ($checkout, $paymentResult) {
            $checkout = $checkout->fresh();
            $cart = $checkout->cart()->with(['lines.variant.product', 'lines.variant.inventoryItem'])->first();
            $totals = $checkout->totals_json ?? [];
            $method = PaymentMethod::from($checkout->payment_method);

            $isInstantCapture = in_array($method, [PaymentMethod::CreditCard, PaymentMethod::Paypal]);

            $order = Order::withoutGlobalScopes()->create([
                'store_id' => $checkout->store_id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $this->generateOrderNumber($checkout->store),
                'payment_method' => $method,
                'status' => $isInstantCapture ? OrderStatus::Paid : OrderStatus::Pending,
                'financial_status' => $isInstantCapture ? FinancialStatus::Paid : FinancialStatus::Pending,
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
                'totals_json' => $totals,
                'placed_at' => now(),
            ]);

            $allDigital = true;

            foreach ($cart->lines as $line) {
                $product = $line->variant->product;
                $variant = $line->variant;
                $variantTitle = $variant->optionValues?->pluck('value')->join(' / ');

                $order->lines()->create([
                    'product_id' => $product?->id,
                    'variant_id' => $variant->id,
                    'title_snapshot' => $product?->title ?? 'Unknown Product',
                    'sku_snapshot' => $variant->sku,
                    'variant_title_snapshot' => $variantTitle ?: null,
                    'price_amount' => $line->unit_price_amount,
                    'quantity' => $line->quantity,
                    'total_amount' => $line->line_total_amount,
                    'requires_shipping' => $variant->requires_shipping ?? true,
                    'tax_lines_json' => [],
                    'discount_allocations_json' => [],
                ]);

                if ($variant->requires_shipping) {
                    $allDigital = false;
                }

                if ($isInstantCapture && $variant->inventoryItem) {
                    $this->inventoryService->commit($variant->inventoryItem, $line->quantity);
                }
            }

            $order->payments()->create([
                'provider' => 'mock',
                'method' => $method,
                'provider_payment_id' => $paymentResult->referenceId,
                'status' => $isInstantCapture ? PaymentStatus::Captured : PaymentStatus::Pending,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'created_at' => now(),
            ]);

            if ($checkout->discount_code) {
                $discount = \App\Models\Discount::withoutGlobalScopes()
                    ->where('store_id', $checkout->store_id)
                    ->whereRaw('LOWER(code) = ?', [strtolower($checkout->discount_code)])
                    ->first();

                if ($discount) {
                    $discount->increment('usage_count');
                }
            }

            $cart->update(['status' => CartStatus::Converted]);
            $checkout->update(['status' => CheckoutStatus::Completed]);

            if ($isInstantCapture && $allDigital) {
                $this->autoFulfillDigitalOrder($order);
            }

            OrderCreated::dispatch($order);

            return $order;
        });
    }

    public function generateOrderNumber(Store $store): string
    {
        $maxNumber = Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->max(DB::raw("CAST(REPLACE(order_number, '#', '') AS INTEGER)"));

        $next = $maxNumber ? $maxNumber + 1 : 1001;

        return '#'.$next;
    }

    public function cancel(Order $order, string $reason): void
    {
        if ($order->fulfillment_status === FulfillmentStatus::Fulfilled) {
            throw new \RuntimeException('Cannot cancel a fulfilled order.');
        }

        DB::transaction(function () use ($order, $reason) {
            $order->loadMissing(['lines.variant.inventoryItem', 'payments']);

            foreach ($order->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    if ($order->financial_status === FinancialStatus::Pending) {
                        $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                    } else {
                        $this->inventoryService->restock($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            foreach ($order->payments as $payment) {
                if ($payment->status === PaymentStatus::Pending) {
                    $payment->update(['status' => PaymentStatus::Failed]);
                }
            }

            $order->update([
                'status' => OrderStatus::Cancelled,
                'financial_status' => $order->financial_status === FinancialStatus::Pending
                    ? FinancialStatus::Voided
                    : $order->financial_status,
                'cancel_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            OrderCancelled::dispatch($order);
        });
    }

    public function confirmBankTransferPayment(Order $order): void
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer) {
            throw new \RuntimeException('Order is not a bank transfer order.');
        }

        if ($order->financial_status !== FinancialStatus::Pending) {
            throw new \RuntimeException('Order payment is not pending.');
        }

        DB::transaction(function () use ($order) {
            $order->loadMissing(['lines.variant.inventoryItem', 'payments']);

            $payment = $order->payments()->where('status', PaymentStatus::Pending->value)->first();
            if ($payment) {
                $payment->update(['status' => PaymentStatus::Captured]);
            }

            foreach ($order->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventoryService->commit($line->variant->inventoryItem, $line->quantity);
                }
            }

            $order->update([
                'status' => OrderStatus::Paid,
                'financial_status' => FinancialStatus::Paid,
            ]);

            $allDigital = $order->lines->every(fn ($line) => ! $line->requires_shipping);
            if ($allDigital) {
                $this->autoFulfillDigitalOrder($order);
            }

            OrderPaid::dispatch($order);
        });
    }

    private function autoFulfillDigitalOrder(Order $order): void
    {
        $order->loadMissing('lines');

        $fulfillment = Fulfillment::create([
            'order_id' => $order->id,
            'status' => FulfillmentShipmentStatus::Delivered,
            'shipped_at' => now(),
            'delivered_at' => now(),
            'created_at' => now(),
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
