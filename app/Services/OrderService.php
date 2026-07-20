<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\CheckoutCompleted;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Scopes\StoreScope;
use App\Models\Store;
use App\ValueObjects\PaymentResult;
use Illuminate\Support\Facades\DB;

/**
 * Order lifecycle (spec 05 §11): creation from a completed checkout,
 * sequential order numbering per store, cancellation, and manual bank
 * transfer payment confirmation.
 */
class OrderService
{
    public function __construct(
        private InventoryService $inventory,
        private PaymentService $payments,
        private FulfillmentService $fulfillments,
        private AnalyticsService $analytics,
    ) {}

    /**
     * Create an order from a checkout whose payment succeeded (spec 05 §6.2
     * completeCheckout steps 3-13). Idempotent: returns the existing order
     * when the checkout was already converted.
     *
     * @param  array<string, mixed>  $paymentDetails
     */
    public function createFromCheckout(Checkout $checkout, PaymentResult $paymentResult, array $paymentDetails = []): Order
    {
        $order = DB::transaction(function () use ($checkout, $paymentResult, $paymentDetails): Order {
            $existing = Order::query()->where('checkout_id', $checkout->id)->first();

            if ($existing !== null) {
                return $existing;
            }

            $checkout->loadMissing([
                'cart.lines.variant.product',
                'cart.lines.variant.inventoryItem',
                'cart.lines.variant.optionValues.option',
                'store.settings',
            ]);

            $cart = $checkout->cart;
            $method = $checkout->payment_method;
            $instantCapture = in_array($method, [PaymentMethod::CreditCard, PaymentMethod::Paypal], true);
            $totals = $checkout->totals_json ?? [];

            $order = Order::create([
                'store_id' => $checkout->store_id,
                'checkout_id' => $checkout->id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $this->generateOrderNumber($checkout->store),
                'payment_method' => $method,
                'status' => $instantCapture ? OrderStatus::Paid : OrderStatus::Pending,
                'financial_status' => $instantCapture ? FinancialStatus::Paid : FinancialStatus::Pending,
                'fulfillment_status' => FulfillmentOrderStatus::Unfulfilled,
                'currency' => $cart->currency,
                'subtotal_amount' => (int) ($totals['subtotal'] ?? 0),
                'discount_amount' => (int) ($totals['discount'] ?? 0),
                'shipping_amount' => (int) ($totals['shipping'] ?? 0),
                'tax_amount' => (int) ($totals['tax'] ?? 0),
                'total_amount' => (int) ($totals['total'] ?? 0),
                'email' => $checkout->email,
                'billing_address_json' => $checkout->billing_address_json,
                'shipping_address_json' => $checkout->shipping_address_json,
                'placed_at' => now(),
            ]);

            $taxDetailsByVariant = collect($checkout->tax_provider_snapshot_json['lines'] ?? [])->keyBy('variant_id');
            $taxName = (string) ($totals['tax_lines'][0]['name'] ?? 'Tax');
            $codeDiscount = $this->resolveCodeDiscount($checkout);

            foreach ($cart->lines as $line) {
                $variant = $line->variant;
                $taxDetail = $taxDetailsByVariant->get($line->variant_id);

                $order->lines()->create([
                    'product_id' => $variant?->product_id,
                    'variant_id' => $line->variant_id,
                    'title_snapshot' => $this->titleSnapshot($line),
                    'sku_snapshot' => $variant?->sku,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'total_amount' => $line->line_total_amount,
                    'tax_lines_json' => ($taxDetail !== null && (int) ($taxDetail['tax_amount'] ?? 0) > 0)
                        ? [['title' => $taxName, 'rate' => (int) $taxDetail['rate'], 'amount' => (int) $taxDetail['tax_amount']]]
                        : [],
                    'discount_allocations_json' => ($codeDiscount !== null && $line->line_discount_amount > 0)
                        ? [['discount_id' => $codeDiscount->id, 'amount' => $line->line_discount_amount]]
                        : [],
                ]);
            }

            $this->payments->recordPayment($order, $method, $paymentResult, $paymentDetails);

            if ($instantCapture) {
                foreach ($cart->lines as $line) {
                    $item = $line->variant?->inventoryItem;

                    if ($item !== null) {
                        $this->inventory->commit($item, $line->quantity);
                    }
                }
            }

            if ($codeDiscount !== null) {
                $codeDiscount->increment('usage_count');
            }

            $cart->forceFill(['status' => CartStatus::Converted])->save();
            $checkout->forceFill(['status' => CheckoutStatus::Completed])->save();

            if ($order->customer_id === null && $checkout->email !== null) {
                $order->forceFill([
                    'customer_id' => $this->linkGuestToCustomer($checkout->email, $checkout->store_id)->id,
                ])->save();
            }

            $order = $order->refresh();

            if ($instantCapture && $order->isDigital()) {
                $this->fulfillments->autoFulfillDigital($order);
            }

            OrderCreated::dispatch($order);
            CheckoutCompleted::dispatch($checkout);

            if ($instantCapture) {
                OrderPaid::dispatch($order->refresh());
            }

            return $order->refresh();
        });

        // Tracked after the transaction commits so analytics can never roll
        // back an order. The deterministic client_event_id keeps the event
        // idempotent across repeated calls. The total in properties feeds
        // the revenue aggregation (spec 05 §14.2).
        $this->analytics->trackSafely($order->store, 'checkout_completed', [
            'order_id' => $order->id,
            'checkout_id' => $checkout->id,
            'order_number' => $order->order_number,
            'total' => $order->total_amount,
            'currency' => $order->currency,
        ], $order->customer_id, 'checkout_completed:checkout:'.$checkout->id);

        return $order;
    }

    /**
     * Next sequential order number for the store (spec 05 §11.2): configured
     * prefix (default "#") + max numeric suffix + 1, starting at the
     * configured start (default 1001). Runs inside the creation transaction.
     */
    public function generateOrderNumber(Store $store): string
    {
        $settings = $store->settings?->settings_json ?? [];
        $prefix = (string) ($settings['order_number_prefix'] ?? '#');
        $start = (int) ($settings['order_number_start'] ?? 1001);

        $max = Order::withoutGlobalScope(StoreScope::class)
            ->where('store_id', $store->id)
            ->where('order_number', 'like', $prefix.'%')
            ->selectRaw('MAX(CAST(SUBSTR(order_number, ?) AS INTEGER)) as aggregate', [mb_strlen($prefix) + 1])
            ->value('aggregate');

        $next = $max === null ? $start : max($start, (int) $max + 1);

        return $prefix.$next;
    }

    /**
     * Cancel an order before fulfillment: releases reserved inventory (bank
     * transfer orders whose stock is still reserved), marks the order
     * cancelled and dispatches OrderCancelled (spec 05 §11).
     *
     * @throws InvalidOrderTransitionException already fulfilled/cancelled/refunded
     */
    public function cancel(Order $order, string $reason): void
    {
        if (in_array($order->status, [OrderStatus::Fulfilled, OrderStatus::Cancelled, OrderStatus::Refunded], true)
            || $order->fulfillment_status === FulfillmentOrderStatus::Fulfilled) {
            throw InvalidOrderTransitionException::make($order->status->value, 'cancel');
        }

        DB::transaction(function () use ($order): void {
            $awaitingPayment = $order->financial_status === FinancialStatus::Pending;

            if ($awaitingPayment) {
                $order->loadMissing('lines.variant.inventoryItem');

                foreach ($order->lines as $line) {
                    $item = $line->variant?->inventoryItem;

                    if ($item !== null) {
                        $this->inventory->release($item, $line->quantity);
                    }
                }

                $order->financial_status = FinancialStatus::Voided;
            }

            $order->status = OrderStatus::Cancelled;
            $order->save();

            $order->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->update(['status' => PaymentStatus::Failed->value]);
        });

        OrderCancelled::dispatch($order, $reason);
    }

    /**
     * Confirm a bank transfer payment was received (spec 05 §10.7): captures
     * the payment, marks the order paid, commits the reserved inventory and
     * auto-fulfills digital orders.
     *
     * @throws InvalidOrderTransitionException wrong method or not pending
     */
    public function confirmBankTransferPayment(Order $order): void
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer) {
            throw InvalidOrderTransitionException::make($order->payment_method->value, 'confirmBankTransferPayment');
        }

        if ($order->financial_status !== FinancialStatus::Pending) {
            throw InvalidOrderTransitionException::make($order->financial_status->value, 'confirmBankTransferPayment');
        }

        DB::transaction(function () use ($order): void {
            $order->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->update(['status' => PaymentStatus::Captured->value]);

            $order->forceFill([
                'financial_status' => FinancialStatus::Paid,
                'status' => OrderStatus::Paid,
            ])->save();

            $order->loadMissing('lines.variant.inventoryItem');

            foreach ($order->lines as $line) {
                $item = $line->variant?->inventoryItem;

                if ($item !== null) {
                    $this->inventory->commit($item, $line->quantity);
                }
            }

            if ($order->isDigital()) {
                $this->fulfillments->autoFulfillDigital($order);
            }
        });

        OrderPaid::dispatch($order->refresh());
    }

    /**
     * Link the checkout email to a customer account (spec 05 §12.4): reuse
     * the store's customer with that email, or create a password-less guest
     * customer that can later claim the account.
     */
    private function linkGuestToCustomer(string $email, int $storeId): Customer
    {
        $customer = Customer::withoutGlobalScope(StoreScope::class)
            ->where('store_id', $storeId)
            ->where('email', $email)
            ->first();

        if ($customer !== null) {
            return $customer;
        }

        return Customer::create([
            'store_id' => $storeId,
            'email' => $email,
            'password_hash' => null,
            'marketing_opt_in' => false,
        ]);
    }

    /**
     * Build the line title snapshot: product title plus variant option labels
     * (spec 05 §6.2 step 6).
     */
    private function titleSnapshot(CartLine $line): string
    {
        $variant = $line->variant;
        $title = $variant?->product?->title ?? 'Unknown product';
        $variantTitle = $variant?->title();

        if ($variantTitle !== null && $variantTitle !== 'Default') {
            $title .= ' - '.$variantTitle;
        }

        return $title;
    }

    /**
     * Resolve the checkout's discount code to the store's discount record.
     */
    private function resolveCodeDiscount(Checkout $checkout): ?Discount
    {
        if ($checkout->discount_code === null) {
            return null;
        }

        return Discount::query()
            ->where('store_id', $checkout->store_id)
            ->whereRaw('lower(code) = ?', [mb_strtolower($checkout->discount_code)])
            ->first();
    }
}
