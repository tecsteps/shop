<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Models\Checkout;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderService
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function createFromCheckout(Checkout $checkout): Order
    {
        return DB::transaction(function () use ($checkout): Order {
            $existing = Order::query()
                ->withoutGlobalScopes()
                ->where('store_id', $checkout->store_id)
                ->where('email', $checkout->email)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->where('total_amount', (int) ($checkout->totals_json['total'] ?? 0))
                ->first();

            if ($existing !== null && $checkout->status === CheckoutStatus::Completed) {
                return $existing;
            }

            $cart = $checkout->cart;
            $lines = $cart->lines()->with('variant.product')->get();
            $totals = $checkout->totals_json ?? [];
            $method = $checkout->payment_method ?? PaymentMethod::CreditCard;

            $inventoryAction = $method === PaymentMethod::BankTransfer ? 'keep_reserved' : 'commit';
            $financial = $method === PaymentMethod::BankTransfer ? FinancialStatus::Pending : FinancialStatus::Paid;
            $status = $method === PaymentMethod::BankTransfer ? OrderStatus::Pending : OrderStatus::Paid;

            $order = Order::query()->create([
                'store_id' => $checkout->store_id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $this->generateOrderNumber(Store::query()->findOrFail($checkout->store_id)),
                'payment_method' => $method->value,
                'status' => $status->value,
                'financial_status' => $financial->value,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled->value,
                'currency' => $cart->currency,
                'subtotal_amount' => (int) ($totals['subtotal'] ?? $cart->subtotal()),
                'discount_amount' => (int) ($totals['discount'] ?? 0),
                'shipping_amount' => (int) ($totals['shipping'] ?? 0),
                'tax_amount' => (int) ($totals['tax_total'] ?? 0),
                'total_amount' => (int) ($totals['total'] ?? $cart->subtotal()),
                'email' => $checkout->email,
                'billing_address_json' => $checkout->billing_address_json,
                'shipping_address_json' => $checkout->shipping_address_json,
                'placed_at' => now(),
            ]);

            foreach ($lines as $line) {
                $variant = $line->variant;
                $product = $variant?->product;

                $orderLine = OrderLine::query()->create([
                    'order_id' => $order->getKey(),
                    'product_id' => $product?->getKey(),
                    'variant_id' => $variant?->getKey(),
                    'title_snapshot' => trim(($product->title ?? 'Item').' '.($variant?->sku ?? '')),
                    'sku_snapshot' => $variant?->sku,
                    'quantity' => (int) $line->quantity,
                    'unit_price_amount' => (int) $line->unit_price_amount,
                    'total_amount' => (int) $line->line_total_amount,
                    'tax_lines_json' => [],
                    'discount_allocations_json' => (int) $line->line_discount_amount > 0
                        ? [['discount_id' => null, 'amount' => (int) $line->line_discount_amount]]
                        : [],
                ]);

                if ($variant !== null && $inventoryAction === 'commit') {
                    $this->inventory->commit($variant, (int) $line->quantity);
                }
                // bank_transfer: inventory remains reserved (was reserved during selectPaymentMethod)
            }

            $cart->status = CartStatus::Converted;
            $cart->save();

            $checkout->status = CheckoutStatus::Completed;
            $checkout->save();

            $this->maybeAutoFulfillDigital($order);

            OrderCreated::dispatch($order);

            if ($financial === FinancialStatus::Paid) {
                OrderPaid::dispatch($order);
            }

            return $order->refresh();
        });
    }

    public function generateOrderNumber(Store $store): string
    {
        $prefix = '#';
        $last = (int) Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->count();

        return $prefix.(1000 + $last + 1);
    }

    public function cancel(Order $order, ?string $reason = null): Order
    {
        if ($order->fulfillment_status === FulfillmentStatus::Fulfilled) {
            throw new RuntimeException('Fulfilled orders cannot be cancelled.');
        }

        return DB::transaction(function () use ($order): Order {
            foreach ($order->lines()->get() as $line) {
                if ($line->variant_id === null) {
                    continue;
                }

                $variant = $line->variant()->withoutGlobalScopes()->first();

                if ($variant === null) {
                    continue;
                }

                if ($order->financial_status === FinancialStatus::Pending) {
                    $this->inventory->release($variant, (int) $line->quantity);
                } else {
                    $this->inventory->restock($variant, (int) $line->quantity);
                }
            }

            $order->status = OrderStatus::Cancelled;
            $order->financial_status = FinancialStatus::Voided;
            $order->save();

            OrderCancelled::dispatch($order);

            return $order->refresh();
        });
    }

    public function close(Order $order): Order
    {
        if ($order->fulfillment_status !== FulfillmentStatus::Fulfilled) {
            throw new RuntimeException('Only fulfilled orders can be closed.');
        }

        $order->status = OrderStatus::Fulfilled;
        $order->save();

        OrderFulfilled::dispatch($order);

        return $order->refresh();
    }

    protected function maybeAutoFulfillDigital(Order $order): void
    {
        $lines = $order->lines()->with('variant')->get();

        if ($lines->isEmpty()) {
            return;
        }

        $allDigital = $lines->every(fn (OrderLine $line): bool => $line->variant === null
            || ! $line->variant->requires_shipping);

        if (! $allDigital) {
            return;
        }

        $fulfillment = Fulfillment::query()->create([
            'order_id' => $order->getKey(),
            'status' => FulfillmentShipmentStatus::Delivered->value,
            'shipped_at' => now(),
            'created_at' => now(),
        ]);

        foreach ($lines as $line) {
            FulfillmentLine::query()->create([
                'fulfillment_id' => $fulfillment->getKey(),
                'order_line_id' => $line->getKey(),
                'quantity' => (int) $line->quantity,
            ]);
        }

        $order->fulfillment_status = FulfillmentStatus::Fulfilled;
        $order->status = OrderStatus::Fulfilled;
        $order->save();
    }
}
