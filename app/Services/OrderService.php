<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use DomainException;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createFromCheckout(Checkout $checkout): Order
    {
        return DB::transaction(function () use ($checkout): Order {
            $cart = $checkout->cart()->with('lines.variant.product')->first();
            /** @var Store $store */
            $store = Store::withoutGlobalScopes()->findOrFail($checkout->store_id);
            $totals = $checkout->totals_json ?? [];

            $currency = $cart?->currency ?? ($totals['currency'] ?? 'USD');

            /** @var Order $order */
            $order = Order::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $this->generateOrderNumber($store),
                'payment_method' => $checkout->payment_method,
                'status' => OrderStatus::Pending->value,
                'financial_status' => FinancialStatus::Pending->value,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled->value,
                'currency' => $currency,
                'subtotal_amount' => (int) ($totals['subtotal'] ?? 0),
                'discount_amount' => (int) ($totals['discount'] ?? 0),
                'shipping_amount' => (int) ($totals['shipping'] ?? 0),
                'tax_amount' => (int) ($totals['tax_total'] ?? 0),
                'total_amount' => (int) ($totals['total'] ?? 0),
                'email' => $checkout->email,
                'billing_address_json' => $checkout->billing_address_json,
                'shipping_address_json' => $checkout->shipping_address_json,
                'placed_at' => now(),
            ]);

            if ($cart !== null) {
                foreach ($cart->lines as $line) {
                    OrderLine::create([
                        'order_id' => $order->id,
                        'product_id' => $line->variant?->product_id,
                        'variant_id' => $line->variant_id,
                        'title_snapshot' => $line->variant?->product?->title ?? 'Removed product',
                        'sku_snapshot' => $line->variant?->sku,
                        'quantity' => (int) $line->quantity,
                        'unit_price_amount' => (int) $line->unit_price_amount,
                        'total_amount' => (int) $line->line_total_amount,
                        'tax_lines_json' => null,
                        'discount_allocations_json' => null,
                    ]);
                }

                $cart->update(['status' => 'converted']);
            }

            $checkout->update(['status' => 'completed']);

            OrderCreated::dispatch($order);

            return $order;
        });
    }

    public function generateOrderNumber(Store $store): string
    {
        $maxNumeric = (int) Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('order_number', 'like', '#%')
            ->whereRaw('CAST(SUBSTR(order_number, 2) AS INTEGER) > 0')
            ->max(DB::raw('CAST(SUBSTR(order_number, 2) AS INTEGER)'));

        $next = max(1000, $maxNumeric) + 1;

        return '#'.$next;
    }

    public function cancel(Order $order, ?string $reason = null): void
    {
        $fulfillmentStatus = $order->fulfillment_status instanceof FulfillmentStatus
            ? $order->fulfillment_status
            : FulfillmentStatus::from((string) $order->fulfillment_status);

        if ($fulfillmentStatus === FulfillmentStatus::Fulfilled) {
            throw new DomainException('Cannot cancel a fulfilled order.');
        }

        DB::transaction(function () use ($order): void {
            $order->loadMissing('lines');

            foreach ($order->lines as $line) {
                if ($line->variant_id === null) {
                    continue;
                }

                $item = InventoryItem::withoutGlobalScopes()
                    ->where('variant_id', $line->variant_id)
                    ->first();

                if ($item !== null) {
                    $item->quantity_reserved = max(0, (int) $item->quantity_reserved - (int) $line->quantity);
                    $item->save();
                }
            }

            $order->update(['status' => OrderStatus::Cancelled->value]);

            OrderCancelled::dispatch($order);
        });
    }

    public function confirmBankTransferPayment(Order $order): void
    {
        $paymentMethod = $order->payment_method instanceof PaymentMethod
            ? $order->payment_method
            : PaymentMethod::from((string) $order->payment_method);

        $financialStatus = $order->financial_status instanceof FinancialStatus
            ? $order->financial_status
            : FinancialStatus::from((string) $order->financial_status);

        if ($paymentMethod !== PaymentMethod::BankTransfer || $financialStatus !== FinancialStatus::Pending) {
            throw new DomainException('Order is not a pending bank transfer.');
        }

        DB::transaction(function () use ($order): void {
            $order->update([
                'financial_status' => FinancialStatus::Paid->value,
                'status' => OrderStatus::Paid->value,
            ]);

            $payment = $order->payments()->latest('id')->first();
            if ($payment !== null) {
                $payment->update(['status' => PaymentStatus::Captured->value]);
            }

            $order->loadMissing('lines');

            foreach ($order->lines as $line) {
                if ($line->variant_id === null) {
                    continue;
                }

                $item = InventoryItem::withoutGlobalScopes()
                    ->where('variant_id', $line->variant_id)
                    ->first();

                if ($item !== null) {
                    $item->quantity_on_hand = max(0, (int) $item->quantity_on_hand - (int) $line->quantity);
                    $item->quantity_reserved = max(0, (int) $item->quantity_reserved - (int) $line->quantity);
                    $item->save();
                }
            }

            OrderPaid::dispatch($order);
        });
    }
}
