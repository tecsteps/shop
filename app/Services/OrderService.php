<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Store;
use App\ValueObjects\PaymentResult;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        public InventoryService $inventoryService,
    ) {}

    public function createFromCheckout(Checkout $checkout, PaymentResult $paymentResult): Order
    {
        return DB::transaction(function () use ($checkout, $paymentResult): Order {
            $checkout->load('cart.lines.variant.product', 'cart.lines.variant.inventoryItem');

            $store = Store::withoutGlobalScopes()->findOrFail($checkout->store_id);
            /** @var Cart $cart */
            $cart = $checkout->cart;
            /** @var array{subtotal?: int, discount?: int, shipping?: int, tax_total?: int, total?: int} $totals */
            $totals = $checkout->totals_json ?? [];

            $paymentMethod = PaymentMethod::from((string) $checkout->payment_method);
            $orderNumber = $this->generateOrderNumber($store);

            $financialStatus = match ($paymentResult->status) {
                'captured' => FinancialStatus::Paid,
                'pending' => FinancialStatus::Pending,
                default => FinancialStatus::Pending,
            };

            $orderStatus = match ($financialStatus) {
                FinancialStatus::Paid => OrderStatus::Paid,
                default => OrderStatus::Pending,
            };

            $order = Order::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $orderNumber,
                'payment_method' => $paymentMethod,
                'status' => $orderStatus,
                'financial_status' => $financialStatus,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'currency' => $cart->currency ?? 'USD',
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

            foreach ($cart->lines as $line) {
                $product = $line->variant?->product;
                /** @var ProductVariant $variant */
                $variant = $line->variant;

                $order->lines()->create([
                    'product_id' => $product?->id,
                    'variant_id' => $variant->id,
                    'title_snapshot' => $product->title ?? 'Unknown Product',
                    'sku_snapshot' => $variant->sku,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'total_amount' => $line->unit_price_amount * $line->quantity,
                    'tax_lines_json' => [],
                    'discount_allocations_json' => [],
                ]);

                if ($variant->inventoryItem) {
                    if (in_array($paymentMethod, [PaymentMethod::CreditCard, PaymentMethod::Paypal])) {
                        $this->inventoryService->commit($variant->inventoryItem, $line->quantity);
                    }
                }
            }

            $paymentStatus = match ($paymentResult->status) {
                'captured' => PaymentStatus::Captured,
                'pending' => PaymentStatus::Pending,
                default => PaymentStatus::Pending,
            };

            $order->payments()->create([
                'provider' => 'mock',
                'method' => $paymentMethod,
                'provider_payment_id' => $paymentResult->providerPaymentId,
                'status' => $paymentStatus,
                'amount' => $totals['total'] ?? 0,
                'currency' => $cart->currency ?? 'USD',
                'raw_json_encrypted' => $paymentResult->rawResponse ? json_encode($paymentResult->rawResponse) : null,
            ]);

            if ($checkout->discount_code) {
                $discount = Discount::withoutGlobalScopes()
                    ->where('store_id', $store->id)
                    ->where('code', $checkout->discount_code)
                    ->first();

                if ($discount) {
                    $discount->increment('usage_count');
                }
            }

            $cart->update(['status' => CartStatus::Converted]);

            $checkout->update(['status' => CheckoutStatus::Completed]);

            OrderCreated::dispatch($order);

            return $order;
        });
    }

    public function generateOrderNumber(Store $store): string
    {
        $maxOrder = Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->orderByRaw("CAST(REPLACE(order_number, '#', '') AS INTEGER) DESC")
            ->first();

        if ($maxOrder) {
            $currentNumber = (int) str_replace('#', '', $maxOrder->order_number);
            $nextNumber = $currentNumber + 1;
        } else {
            $nextNumber = 1001;
        }

        return '#'.$nextNumber;
    }

    public function cancel(Order $order, string $reason = ''): void
    {
        if ($order->fulfillment_status !== FulfillmentStatus::Unfulfilled) {
            throw new \InvalidArgumentException('Cannot cancel an order that has been partially or fully fulfilled.');
        }

        DB::transaction(function () use ($order): void {
            $order->load('lines.variant.inventoryItem');

            foreach ($order->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventoryService->restock($line->variant->inventoryItem, $line->quantity);
                }
            }

            $order->update([
                'status' => OrderStatus::Cancelled,
            ]);

            OrderCancelled::dispatch($order);
        });
    }

    public function confirmBankTransferPayment(Order $order): void
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer) {
            throw new \InvalidArgumentException('Order is not a bank transfer order.');
        }

        if ($order->financial_status !== FinancialStatus::Pending) {
            throw new \InvalidArgumentException('Order payment has already been confirmed.');
        }

        DB::transaction(function () use ($order): void {
            $order->load('lines.variant.inventoryItem');

            $order->update([
                'financial_status' => FinancialStatus::Paid,
                'status' => OrderStatus::Paid,
            ]);

            $order->payments()
                ->where('method', PaymentMethod::BankTransfer)
                ->where('status', PaymentStatus::Pending)
                ->update(['status' => PaymentStatus::Captured]);

            foreach ($order->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventoryService->commit($line->variant->inventoryItem, $line->quantity);
                }
            }
        });
    }
}
