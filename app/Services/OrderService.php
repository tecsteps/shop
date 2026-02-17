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
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Exceptions\PaymentDeclinedException;
use App\Models\Checkout;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private PaymentProvider $paymentProvider,
        private InventoryService $inventoryService,
    ) {}

    /**
     * @param  array<string, mixed>  $paymentDetails
     */
    public function createFromCheckout(Checkout $checkout, array $paymentDetails = []): Order
    {
        return DB::transaction(function () use ($checkout, $paymentDetails) {
            // Idempotency: check if order already exists for this checkout
            $existingOrder = Order::query()
                ->withoutGlobalScopes()
                ->where('store_id', $checkout->store_id)
                ->whereHas('payments', function ($q) {
                    $q->where('provider_payment_id', 'like', 'mock_%');
                })
                ->whereRaw('placed_at IS NOT NULL')
                ->where('email', $checkout->email)
                ->first();

            if ($existingOrder && $checkout->status === CheckoutStatus::Completed) {
                return $existingOrder;
            }

            $method = PaymentMethod::from($checkout->payment_method);

            // Charge via provider
            $paymentResult = $this->paymentProvider->charge($checkout, $method, $paymentDetails);

            if (! $paymentResult->success) {
                // Release reserved inventory
                $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();
                foreach ($cart->lines as $line) {
                    if ($line->variant && $line->variant->inventoryItem) {
                        $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                    }
                }

                throw new PaymentDeclinedException($paymentResult->errorCode ?? 'unknown');
            }

            // Determine statuses based on payment method
            $isInstantCapture = in_array($method, [PaymentMethod::CreditCard, PaymentMethod::Paypal]);

            $orderStatus = $isInstantCapture ? OrderStatus::Paid : OrderStatus::Pending;
            $financialStatus = $isInstantCapture ? FinancialStatus::Paid : FinancialStatus::Pending;
            $paymentStatus = $isInstantCapture ? PaymentStatus::Captured : PaymentStatus::Pending;

            $totals = $checkout->totals_json ?? [];

            $order = Order::create([
                'store_id' => $checkout->store_id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $this->generateOrderNumber($checkout->store_id),
                'payment_method' => $method,
                'status' => $orderStatus,
                'financial_status' => $financialStatus,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'currency' => $totals['currency'] ?? 'EUR',
                'subtotal_amount' => $totals['subtotal'] ?? 0,
                'discount_amount' => $totals['discount'] ?? 0,
                'shipping_amount' => $totals['shipping'] ?? 0,
                'tax_amount' => $totals['tax_total'] ?? 0,
                'total_amount' => $totals['total'] ?? 0,
                'email' => $checkout->email,
                'billing_address_json' => $checkout->billing_address_json,
                'shipping_address_json' => $checkout->shipping_address_json,
                'discount_code' => $checkout->discount_code,
                'totals_json' => $totals,
                'placed_at' => now(),
            ]);

            // Create order lines from cart
            $cart = $checkout->cart()->with('lines.variant.product', 'lines.variant.inventoryItem')->first();

            foreach ($cart->lines as $line) {
                $variant = $line->variant;
                $product = $variant->product;

                $order->lines()->create([
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'title_snapshot' => $product->title,
                    'sku_snapshot' => $variant->sku,
                    'variant_title_snapshot' => $variant->is_default ? null : $variant->sku,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'subtotal_amount' => $line->line_subtotal_amount,
                    'tax_amount' => 0,
                    'discount_amount' => $line->line_discount_amount,
                    'total_amount' => $line->line_total_amount,
                    'requires_shipping' => $variant->requires_shipping,
                ]);

                // Commit or keep reserved based on payment method
                if ($isInstantCapture && $variant->inventoryItem) {
                    $this->inventoryService->commit($variant->inventoryItem, $line->quantity);
                }
            }

            // Create payment record
            $order->payments()->create([
                'provider' => 'mock',
                'method' => $method,
                'provider_payment_id' => $paymentResult->providerPaymentId,
                'status' => $paymentStatus,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'captured_at' => $isInstantCapture ? now() : null,
                'created_at' => now(),
            ]);

            // Increment discount usage
            if ($checkout->discount_code) {
                \App\Models\Discount::query()
                    ->withoutGlobalScopes()
                    ->where('store_id', $checkout->store_id)
                    ->whereRaw('LOWER(code) = ?', [strtolower($checkout->discount_code)])
                    ->increment('usage_count');
            }

            // Mark cart as converted
            $cart->update(['status' => CartStatus::Converted]);

            // Mark checkout as completed
            $checkout->update(['status' => CheckoutStatus::Completed]);

            // Auto-fulfill digital products
            if ($isInstantCapture) {
                $this->autoFulfillDigitalProducts($order);
            }

            OrderCreated::dispatch($order);

            return $order;
        });
    }

    public function generateOrderNumber(int $storeId): string
    {
        $maxNumber = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->lockForUpdate()
            ->max('order_number');

        if ($maxNumber) {
            $numericPart = (int) ltrim($maxNumber, '#');
            $nextNumber = $numericPart + 1;
        } else {
            $nextNumber = 1001;
        }

        return '#'.$nextNumber;
    }

    public function cancel(Order $order, string $reason): void
    {
        if ($order->fulfillment_status !== FulfillmentStatus::Unfulfilled) {
            throw new \RuntimeException('Cannot cancel an order that has been partially or fully fulfilled.');
        }

        DB::transaction(function () use ($order, $reason) {
            // Release reserved inventory for bank transfer orders
            if ($order->financial_status === FinancialStatus::Pending) {
                $order->load('lines.variant.inventoryItem');
                foreach ($order->lines as $line) {
                    if ($line->variant && $line->variant->inventoryItem) {
                        $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                    }
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

            // Mark payment as failed if pending
            $order->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->update(['status' => PaymentStatus::Failed->value]);

            OrderCancelled::dispatch($order);
        });
    }

    /**
     * Confirm bank transfer payment (admin action).
     */
    public function confirmBankTransferPayment(Order $order): void
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer) {
            throw new \RuntimeException('Order is not a bank transfer order.');
        }

        if ($order->financial_status !== FinancialStatus::Pending) {
            throw new \RuntimeException('Order financial status is not pending.');
        }

        DB::transaction(function () use ($order) {
            $order->update([
                'status' => OrderStatus::Paid,
                'financial_status' => FinancialStatus::Paid,
            ]);

            $order->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->update([
                    'status' => PaymentStatus::Captured->value,
                    'captured_at' => now(),
                ]);

            // Commit reserved inventory
            $order->load('lines.variant.inventoryItem');
            foreach ($order->lines as $line) {
                if ($line->variant && $line->variant->inventoryItem) {
                    $this->inventoryService->commit($line->variant->inventoryItem, $line->quantity);
                }
            }

            // Auto-fulfill digital products
            $this->autoFulfillDigitalProducts($order);

            \App\Events\OrderPaid::dispatch($order);
        });
    }

    private function autoFulfillDigitalProducts(Order $order): void
    {
        $order->load('lines');

        $allDigital = $order->lines->every(fn ($line) => ! $line->requires_shipping);

        if (! $allDigital || $order->lines->isEmpty()) {
            return;
        }

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
