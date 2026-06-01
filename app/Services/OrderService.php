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
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Store;
use App\ValueObjects\PaymentResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Builds orders from completed checkouts and owns order-level lifecycle actions.
 *
 * Order creation is atomic: the order, its snapshot line items, and the payment
 * record are written together; inventory is committed (card/PayPal) or kept
 * reserved (bank transfer); the cart is converted; discount usage is recorded;
 * and {@see OrderCreated} is dispatched. Order numbers are sequential per store
 * starting at the configured base (default #1001).
 */
class OrderService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly CustomerService $customers,
        private readonly FulfillmentService $fulfillments,
    ) {}

    /**
     * Create an order from a checkout and a successful payment result.
     *
     * Expects the caller to have already charged the payment. Commits or keeps
     * inventory reserved according to the payment method.
     */
    public function createFromCheckout(Checkout $checkout, PaymentResult $paymentResult): Order
    {
        return DB::transaction(function () use ($checkout, $paymentResult): Order {
            $cart = $checkout->cart->loadMissing('lines.variant.product');
            $totals = $checkout->totals_json ?? [];
            $method = $checkout->payment_method;

            $instantCapture = $method !== PaymentMethod::BankTransfer;

            $order = Order::create([
                'store_id' => $checkout->store_id,
                'customer_id' => $this->resolveCustomerId($checkout),
                'checkout_id' => $checkout->id,
                'order_number' => $this->generateOrderNumber($checkout->store),
                'payment_method' => $method->value,
                'status' => $instantCapture ? OrderStatus::Paid->value : OrderStatus::Pending->value,
                'financial_status' => $instantCapture ? FinancialStatus::Paid->value : FinancialStatus::Pending->value,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled->value,
                'currency' => $cart->currency,
                'subtotal_amount' => (int) ($totals['subtotal'] ?? $cart->subtotalAmount()),
                'discount_amount' => (int) ($totals['discount'] ?? 0),
                'shipping_amount' => (int) ($totals['shipping'] ?? 0),
                'tax_amount' => (int) ($totals['tax'] ?? 0),
                'total_amount' => (int) ($totals['total'] ?? $cart->subtotalAmount()),
                'email' => $checkout->email,
                'billing_address_json' => $checkout->billing_address_json,
                'shipping_address_json' => $checkout->shipping_address_json,
                'placed_at' => Carbon::now(),
            ]);

            $this->createOrderLines($order, $cart);
            $this->createPayment($order, $checkout, $paymentResult, $instantCapture);

            if ($instantCapture) {
                $this->commitInventory($cart);
            }

            $this->recordDiscountUsage($checkout);

            $cart->update(['status' => CartStatus::Converted->value]);
            $checkout->update(['status' => CheckoutStatus::Completed->value]);

            if ($instantCapture) {
                $this->fulfillments->autoFulfillDigital($order);
            }

            OrderCreated::dispatch($order->refresh());

            return $order;
        });
    }

    /**
     * Generate the next sequential order number for a store.
     *
     * Runs inside the order-creation transaction; SQLite's single-writer model
     * guarantees uniqueness without extra locking.
     */
    public function generateOrderNumber(Store $store): string
    {
        $prefix = $this->orderNumberPrefix($store);
        $start = (int) config('shop.order_number_start', 1001);

        $max = Order::query()
            ->where('store_id', $store->id)
            ->get(['order_number'])
            ->map(fn (Order $order): int => (int) preg_replace('/\D/', '', $order->order_number))
            ->max();

        $next = $max === null ? $start : max($max + 1, $start);

        return $prefix.$next;
    }

    /**
     * Cancel an order that has not yet been fulfilled, releasing any reserved
     * inventory and voiding its pending payment.
     */
    public function cancel(Order $order, ?string $reason = null): void
    {
        if ($order->fulfillment_status !== FulfillmentStatus::Unfulfilled) {
            throw new RuntimeException('A fulfilled order cannot be cancelled.');
        }

        if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Refunded], true)) {
            throw new RuntimeException('This order is already cancelled or refunded.');
        }

        DB::transaction(function () use ($order): void {
            if ($order->financial_status === FinancialStatus::Pending) {
                $this->releaseInventory($order);
            }

            $order->update([
                'status' => OrderStatus::Cancelled->value,
                'financial_status' => FinancialStatus::Voided->value,
            ]);

            $order->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->update(['status' => PaymentStatus::Failed->value]);
        });

        OrderCancelled::dispatch($order->refresh());
    }

    private function createOrderLines(Order $order, $cart): void
    {
        foreach ($cart->lines as $line) {
            $variant = $line->variant;
            $product = $variant?->product;

            $order->lines()->create([
                'store_id' => $order->store_id,
                'product_id' => $product?->id,
                'variant_id' => $variant?->id,
                'title_snapshot' => $this->titleSnapshot($product, $variant),
                'sku_snapshot' => $variant?->sku,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'total_amount' => $line->line_total_amount,
                'tax_lines_json' => [],
                'discount_allocations_json' => $line->line_discount_amount > 0
                    ? [['amount' => $line->line_discount_amount]]
                    : [],
            ]);
        }
    }

    private function createPayment(Order $order, Checkout $checkout, PaymentResult $result, bool $instantCapture): Payment
    {
        return $order->payments()->create([
            'provider' => 'mock',
            'method' => $checkout->payment_method->value,
            'provider_payment_id' => $result->referenceId,
            'status' => ($instantCapture ? PaymentStatus::Captured : PaymentStatus::Pending)->value,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'raw_json_encrypted' => $result->raw,
        ]);
    }

    private function commitInventory($cart): void
    {
        foreach ($cart->lines as $line) {
            $item = $line->variant?->inventoryItem;

            if ($item !== null) {
                $this->inventory->commit($item, $line->quantity);
            }
        }
    }

    private function releaseInventory(Order $order): void
    {
        foreach ($order->lines as $line) {
            $item = $line->variant_id === null
                ? null
                : ProductVariant::query()->find($line->variant_id)?->inventoryItem;

            if ($item !== null) {
                $this->inventory->release($item, $line->quantity);
            }
        }
    }

    private function recordDiscountUsage(Checkout $checkout): void
    {
        if ($checkout->discount_code === null || $checkout->discount_code === '') {
            return;
        }

        Discount::query()
            ->where('store_id', $checkout->store_id)
            ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($checkout->discount_code))])
            ->increment('usage_count');
    }

    private function resolveCustomerId(Checkout $checkout): ?int
    {
        if ($checkout->customer_id !== null) {
            return $checkout->customer_id;
        }

        if ($checkout->email === null) {
            return null;
        }

        return $this->customers->findOrCreateGuest($checkout->store, $checkout->email)->id;
    }

    private function titleSnapshot($product, ?ProductVariant $variant): string
    {
        $title = $product?->title ?? 'Unknown product';

        if ($variant === null) {
            return $title;
        }

        $options = $variant->relationLoaded('optionValues')
            ? $variant->optionValues->pluck('value')->implode(' / ')
            : $variant->optionValues()->pluck('value')->implode(' / ');

        return $options === '' ? $title : "{$title} - {$options}";
    }

    private function orderNumberPrefix(Store $store): string
    {
        $settings = $store->relationLoaded('settings') ? $store->settings : $store->settings()->first();
        $prefix = $settings?->settings_json['order_number_prefix'] ?? null;

        return $prefix ?? (string) config('shop.order_number_prefix', '#');
    }
}
