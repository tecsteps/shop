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
use App\Events\OrderPaid;
use App\Exceptions\PaymentFailedException;
use App\Models\Checkout;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected PaymentProvider $paymentProvider,
        protected InventoryService $inventoryService,
    ) {}

    public function createFromCheckout(Checkout $checkout, array $paymentDetails = []): Order
    {
        // Charge payment outside the transaction so inventory release is not rolled back on failure
        $paymentResult = $this->paymentProvider->charge(
            $checkout,
            $checkout->payment_method,
            $paymentDetails,
        );

        if (! $paymentResult->success && $paymentResult->status === 'failed') {
            // Release reserved inventory on payment failure
            $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();
            foreach ($cart->lines as $line) {
                if ($line->variant && $line->variant->inventoryItem) {
                    $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                }
            }

            throw new PaymentFailedException($paymentResult->error ?? 'Payment failed.');
        }

        $isBankTransfer = $checkout->payment_method === 'bank_transfer';

        return DB::transaction(function () use ($checkout, $paymentResult, $isBankTransfer) {

            $order = Order::create([
                'store_id' => $checkout->store_id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $this->generateOrderNumber($checkout->store),
                'payment_method' => $checkout->payment_method,
                'status' => $isBankTransfer ? OrderStatus::Pending : OrderStatus::Paid,
                'financial_status' => $isBankTransfer ? FinancialStatus::Pending : FinancialStatus::Paid,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'currency' => $checkout->cart->currency ?? 'USD',
                'subtotal_amount' => $checkout->totals_json['subtotal'] ?? 0,
                'discount_amount' => $checkout->totals_json['discount'] ?? 0,
                'shipping_amount' => $checkout->totals_json['shipping'] ?? 0,
                'tax_amount' => $checkout->totals_json['tax'] ?? $checkout->totals_json['tax_total'] ?? 0,
                'total_amount' => $checkout->totals_json['total'] ?? 0,
                'email' => $checkout->email,
                'billing_address_json' => $checkout->billing_address_json,
                'shipping_address_json' => $checkout->shipping_address_json,
                'placed_at' => now(),
            ]);

            // Create order lines from cart lines
            $cart = $checkout->cart()->with('lines.variant.product', 'lines.variant.inventoryItem')->first();

            foreach ($cart->lines as $cartLine) {
                $variant = $cartLine->variant;
                $product = $variant?->product;

                $order->lines()->create([
                    'product_id' => $product?->id,
                    'variant_id' => $variant?->id,
                    'title_snapshot' => $product?->title ?? 'Unknown Product',
                    'sku_snapshot' => $variant?->sku,
                    'quantity' => $cartLine->quantity,
                    'unit_price_amount' => $cartLine->unit_price_amount,
                    'total_amount' => $cartLine->line_total_amount,
                    'tax_lines_json' => [],
                    'discount_allocations_json' => [],
                ]);

                // Commit or keep reserved inventory depending on payment method
                if ($variant && $variant->inventoryItem) {
                    if (! $isBankTransfer) {
                        $this->inventoryService->commit($variant->inventoryItem, $cartLine->quantity);
                    }
                }
            }

            // Create payment record
            $order->payments()->create([
                'provider' => 'mock',
                'method' => $checkout->payment_method,
                'provider_payment_id' => $paymentResult->providerPaymentId,
                'status' => $isBankTransfer ? PaymentStatus::Pending : PaymentStatus::Captured,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'raw_json_encrypted' => Crypt::encryptString(json_encode([
                    'success' => $paymentResult->success,
                    'status' => $paymentResult->status,
                    'provider_payment_id' => $paymentResult->providerPaymentId,
                ])),
                'created_at' => now(),
            ]);

            // Mark cart as converted
            $cart->update(['status' => CartStatus::Converted]);

            // Mark checkout as completed
            $checkout->update(['status' => CheckoutStatus::Completed]);

            // Auto-fulfill digital products for non-bank-transfer
            if (! $isBankTransfer) {
                $this->autoFulfillDigitalProducts($order);
            }

            OrderCreated::dispatch($order);

            if (! $isBankTransfer) {
                OrderPaid::dispatch($order);
            }

            return $order;
        });
    }

    public function generateOrderNumber(Store $store): string
    {
        $maxNumber = Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->max(DB::raw("CAST(REPLACE(order_number, '#', '') AS INTEGER)"));

        $nextNumber = $maxNumber ? $maxNumber + 1 : 1001;

        return '#'.$nextNumber;
    }

    public function cancel(Order $order, string $reason = ''): void
    {
        if ($order->fulfillment_status !== FulfillmentStatus::Unfulfilled) {
            throw new \RuntimeException('Cannot cancel an order that has been partially or fully fulfilled.');
        }

        DB::transaction(function () use ($order) {
            // Release inventory for each order line
            $order->load('lines.variant.inventoryItem');

            foreach ($order->lines as $line) {
                if ($line->variant && $line->variant->inventoryItem) {
                    if ($order->financial_status === FinancialStatus::Pending) {
                        // Bank transfer: inventory was reserved, release it
                        $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                    } else {
                        // Credit card / PayPal: inventory was committed, restock it
                        $this->inventoryService->restock($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            $order->update([
                'status' => OrderStatus::Cancelled,
                'financial_status' => $order->financial_status === FinancialStatus::Pending
                    ? FinancialStatus::Voided
                    : $order->financial_status,
            ]);

            // Mark payment as failed if pending
            $order->payments()->where('status', PaymentStatus::Pending)->update([
                'status' => PaymentStatus::Failed->value,
            ]);
        });

        OrderCancelled::dispatch($order->fresh());
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
            $order->update([
                'status' => OrderStatus::Paid,
                'financial_status' => FinancialStatus::Paid,
            ]);

            $order->payments()->where('status', PaymentStatus::Pending)->update([
                'status' => PaymentStatus::Captured->value,
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
        });

        OrderPaid::dispatch($order->fresh());
    }

    protected function autoFulfillDigitalProducts(Order $order): void
    {
        $order->load('lines.variant');

        $allDigital = $order->lines->every(function ($line) {
            return $line->variant && ! $line->variant->requires_shipping;
        });

        if (! $allDigital || $order->lines->isEmpty()) {
            return;
        }

        $fulfillment = Fulfillment::create([
            'order_id' => $order->id,
            'status' => FulfillmentShipmentStatus::Delivered,
            'shipped_at' => now(),
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
