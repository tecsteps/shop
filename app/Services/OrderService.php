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
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        private PaymentProvider $paymentProvider,
        private InventoryService $inventoryService,
    ) {}

    /**
     * Create an order from a completed checkout.
     *
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function createFromCheckout(Checkout $checkout, array $paymentMethodData = []): Order
    {
        // Idempotency: if checkout is already completed, return existing order
        if ($checkout->status === CheckoutStatus::Completed) {
            $existingOrder = Order::withoutGlobalScopes()
                ->where('store_id', $checkout->store_id)
                ->where('email', $checkout->email)
                ->where('total_amount', $checkout->totals_json['total'] ?? 0)
                ->first();

            if ($existingOrder) {
                return $existingOrder;
            }
        }

        // Charge via payment provider (outside transaction so failure handling works)
        $paymentResult = $this->paymentProvider->charge($checkout, $paymentMethodData);

        if (! $paymentResult->success) {
            // Release reserved inventory (outside transaction so it persists)
            $cart = Cart::withoutGlobalScopes()->findOrFail($checkout->cart_id);
            foreach ($cart->lines()->with('variant.inventoryItem')->get() as $cartLine) {
                if ($cartLine->variant && $cartLine->variant->inventoryItem) {
                    $this->inventoryService->release($cartLine->variant->inventoryItem, $cartLine->quantity);
                }
            }

            throw new PaymentFailedException($paymentResult->errorCode ?? 'unknown', $paymentResult->errorMessage ?? 'Payment failed');
        }

        return DB::transaction(function () use ($checkout, $paymentResult) {
            // Determine statuses based on payment method
            $paymentMethodEnum = $checkout->payment_method instanceof PaymentMethod
                ? $checkout->payment_method
                : PaymentMethod::from($checkout->payment_method);

            $isBankTransfer = $paymentMethodEnum === PaymentMethod::BankTransfer;

            $orderStatus = $isBankTransfer ? OrderStatus::Pending : OrderStatus::Paid;
            $financialStatus = $isBankTransfer ? FinancialStatus::Pending : FinancialStatus::Paid;
            $paymentStatus = $isBankTransfer ? PaymentStatus::Pending : PaymentStatus::Captured;

            // Generate order number
            $orderNumber = $this->generateOrderNumber(
                Store::withoutGlobalScopes()->findOrFail($checkout->store_id),
            );

            $totals = $checkout->totals_json ?? [];

            // Create the order
            $order = Order::withoutGlobalScopes()->create([
                'store_id' => $checkout->store_id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $orderNumber,
                'payment_method' => $paymentMethodEnum->value,
                'status' => $orderStatus,
                'financial_status' => $financialStatus,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'currency' => $totals['currency'] ?? 'USD',
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

            // Create order lines from cart
            $cart = Cart::withoutGlobalScopes()->with('lines.variant.product')->findOrFail($checkout->cart_id);

            foreach ($cart->lines as $cartLine) {
                $variant = $cartLine->variant;
                $product = $variant?->product;

                $titleSnapshot = $product ? $product->title : 'Deleted Product';
                if ($variant && $variant->optionValues()->exists()) {
                    $optionLabels = $variant->optionValues()
                        ->get()
                        ->pluck('value')
                        ->implode(' / ');
                    if ($optionLabels) {
                        $titleSnapshot .= ' - '.$optionLabels;
                    }
                }

                $order->lines()->create([
                    'product_id' => $product?->id,
                    'variant_id' => $variant?->id,
                    'title_snapshot' => $titleSnapshot,
                    'sku_snapshot' => $variant?->sku,
                    'quantity' => $cartLine->quantity,
                    'unit_price_amount' => $cartLine->unit_price_amount,
                    'total_amount' => $cartLine->line_total_amount,
                    'tax_lines_json' => [],
                    'discount_allocations_json' => $cartLine->line_discount_amount > 0
                        ? [['amount' => $cartLine->line_discount_amount]]
                        : [],
                ]);
            }

            // Create payment record
            Payment::create([
                'order_id' => $order->id,
                'provider' => 'mock',
                'method' => $paymentMethodEnum->value,
                'provider_payment_id' => $paymentResult->referenceId,
                'status' => $paymentStatus,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'raw_json_encrypted' => json_encode([
                    'reference_id' => $paymentResult->referenceId,
                    'status' => $paymentResult->status,
                ]),
                'created_at' => now(),
            ]);

            // Commit or keep reserved inventory
            if (! $isBankTransfer) {
                foreach ($cart->lines()->with('variant.inventoryItem')->get() as $cartLine) {
                    if ($cartLine->variant && $cartLine->variant->inventoryItem) {
                        $this->inventoryService->commit($cartLine->variant->inventoryItem, $cartLine->quantity);
                    }
                }
            }

            // Increment discount usage if applicable
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

            // Auto-fulfill digital products (only for paid orders)
            if (! $isBankTransfer) {
                $this->autoFulfillDigitalProducts($order);
            }

            // Dispatch event
            OrderCreated::dispatch($order);

            return $order;
        });
    }

    /**
     * Generate a sequential order number for a store.
     */
    public function generateOrderNumber(Store $store): string
    {
        $maxNumber = Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->max('order_number');

        if ($maxNumber === null) {
            return '#1001';
        }

        // Extract numeric part
        $numeric = (int) ltrim($maxNumber, '#');

        return '#'.($numeric + 1);
    }

    /**
     * Cancel an order. Only if not fulfilled, releases inventory.
     */
    public function cancel(Order $order, ?string $reason = null): Order
    {
        if ($order->fulfillment_status !== FulfillmentStatus::Unfulfilled) {
            throw new InvalidArgumentException('Cannot cancel an order that has been partially or fully fulfilled.');
        }

        return DB::transaction(function () use ($order, $reason) {
            // Release reserved inventory for bank transfer orders
            if ($order->financial_status === FinancialStatus::Pending) {
                foreach ($order->lines()->with('variant.inventoryItem')->get() as $line) {
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
            ]);

            // Update payment status
            if ($order->financial_status === FinancialStatus::Voided) {
                $order->payments()->update(['status' => PaymentStatus::Failed]);
            }

            OrderCancelled::dispatch($order, $reason);

            return $order->refresh();
        });
    }

    /**
     * Confirm payment for a bank transfer order.
     */
    public function confirmPayment(Order $order): Order
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer) {
            throw new InvalidArgumentException('Can only confirm payment for bank transfer orders.');
        }

        if ($order->financial_status !== FinancialStatus::Pending) {
            throw new InvalidArgumentException('Order financial status must be pending to confirm payment.');
        }

        return DB::transaction(function () use ($order) {
            // Update payment status
            $order->payments()
                ->where('status', PaymentStatus::Pending)
                ->update(['status' => PaymentStatus::Captured]);

            // Update order status
            $order->update([
                'status' => OrderStatus::Paid,
                'financial_status' => FinancialStatus::Paid,
            ]);

            // Commit inventory (convert reserved to committed)
            foreach ($order->lines()->with('variant.inventoryItem')->get() as $line) {
                if ($line->variant && $line->variant->inventoryItem) {
                    $this->inventoryService->commit($line->variant->inventoryItem, $line->quantity);
                }
            }

            // Auto-fulfill digital products
            $this->autoFulfillDigitalProducts($order);

            OrderPaid::dispatch($order);

            return $order->refresh();
        });
    }

    /**
     * Auto-fulfill if all order lines are digital products.
     */
    private function autoFulfillDigitalProducts(Order $order): void
    {
        $orderLines = $order->lines()->with('variant')->get();

        if ($orderLines->isEmpty()) {
            return;
        }

        $allDigital = $orderLines->every(function ($line) {
            return $line->variant && ! $line->variant->requires_shipping;
        });

        if (! $allDigital) {
            return;
        }

        $fulfillment = Fulfillment::create([
            'order_id' => $order->id,
            'status' => FulfillmentShipmentStatus::Delivered,
            'shipped_at' => now(),
            'created_at' => now(),
        ]);

        foreach ($orderLines as $line) {
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
