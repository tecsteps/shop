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
use App\Exceptions\PaymentFailedException;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $checkout->loadMissing(['cart.lines.variant.product', 'cart.lines.variant.inventoryItem']);

        // Idempotent: check if an order already exists for this checkout
        $existingOrder = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $checkout->store_id)
            ->where('email', $checkout->email)
            ->whereHas('lines', function ($q) use ($checkout) {
                $q->whereIn('variant_id', $checkout->cart->lines->pluck('variant_id'));
            })
            ->first();

        // A more reliable idempotency check would use a checkout_id column,
        // but for now we rely on the checkout status check
        if ($checkout->status === CheckoutStatus::Completed) {
            if ($existingOrder) {
                return $existingOrder;
            }
        }

        $method = PaymentMethod::from((string) $checkout->payment_method);

        // Charge via payment provider
        $paymentResult = $this->paymentProvider->charge($checkout, $method, $paymentMethodData);

        if (! $paymentResult->success) {
            // Release reserved inventory on failed payment
            foreach ($checkout->cart->lines as $line) {
                if ($line->variant->inventoryItem) {
                    $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                }
            }

            throw new PaymentFailedException(
                $paymentResult->errorCode ?? 'unknown',
                $paymentResult->errorMessage,
            );
        }

        /** @var Order $result */
        $result = DB::transaction(function () use ($checkout, $method, $paymentResult) {
            // Determine statuses based on payment method
            $isBankTransfer = $method === PaymentMethod::BankTransfer;
            $orderStatus = $isBankTransfer ? OrderStatus::Pending : OrderStatus::Paid;
            $financialStatus = $isBankTransfer ? FinancialStatus::Pending : FinancialStatus::Paid;
            $paymentStatus = $isBankTransfer ? PaymentStatus::Pending : PaymentStatus::Captured;

            $totals = $checkout->totals_json ?? [];
            $orderNumber = $this->generateOrderNumber($checkout->store_id);

            /** @var Order $order */
            $order = Order::query()->withoutGlobalScopes()->create([
                'store_id' => $checkout->store_id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $orderNumber,
                'payment_method' => $method,
                'status' => $orderStatus,
                'financial_status' => $financialStatus,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'currency' => $checkout->cart->currency,
                'subtotal_amount' => $totals['subtotal'] ?? 0,
                'discount_amount' => $totals['discount'] ?? 0,
                'shipping_amount' => $totals['shipping'] ?? 0,
                'tax_amount' => $totals['taxTotal'] ?? 0,
                'total_amount' => $totals['total'] ?? 0,
                'email' => $checkout->email,
                'billing_address_json' => $checkout->billing_address_json,
                'shipping_address_json' => $checkout->shipping_address_json,
                'placed_at' => now(),
            ]);

            // Create order lines with snapshots
            foreach ($checkout->cart->lines as $line) {
                $variant = $line->variant;
                $product = $variant->product;

                OrderLine::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'title_snapshot' => $product->title,
                    'sku_snapshot' => $variant->sku,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'total_amount' => $line->line_total_amount,
                    'tax_lines_json' => [],
                    'discount_allocations_json' => [],
                ]);
            }

            // Create payment record
            Payment::query()->create([
                'order_id' => $order->id,
                'provider' => 'mock',
                'method' => $method,
                'provider_payment_id' => $paymentResult->providerPaymentId,
                'status' => $paymentStatus,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'created_at' => now(),
            ]);

            // Commit or keep reserved inventory
            if (! $isBankTransfer) {
                foreach ($checkout->cart->lines as $line) {
                    if ($line->variant->inventoryItem) {
                        $this->inventoryService->commit($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            // Increment discount usage
            if ($checkout->discount_code) {
                Discount::query()
                    ->withoutGlobalScopes()
                    ->where('store_id', $checkout->store_id)
                    ->whereRaw('LOWER(code) = ?', [strtolower($checkout->discount_code)])
                    ->increment('usage_count');
            }

            // Mark cart as converted
            $checkout->cart->update(['status' => CartStatus::Converted]);

            // Mark checkout as completed
            $checkout->update([
                'status' => CheckoutStatus::Completed,
                'completed_at' => now(),
            ]);

            // Auto-fulfill digital products for paid orders
            if (! $isBankTransfer) {
                $this->autoFulfillDigitalProducts($order);
            }

            OrderCreated::dispatch($order);

            Log::channel('json')->info('Order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'store_id' => $order->store_id,
                'customer_id' => $order->customer_id,
                'total_amount' => $order->total_amount,
                'payment_method' => $method->value,
                'financial_status' => $financialStatus->value,
            ]);

            return $order;
        });

        return $result;
    }

    /**
     * Generate the next sequential order number for a store.
     */
    public function generateOrderNumber(int $storeId): string
    {
        /** @var int|null $maxNumber */
        $maxNumber = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->selectRaw("MAX(CAST(REPLACE(order_number, '#', '') AS INTEGER)) as max_num")
            ->value('max_num');

        $nextNumber = ($maxNumber ?: 1000) + 1;

        return '#'.$nextNumber;
    }

    /**
     * Cancel an order (only if not fulfilled).
     */
    public function cancel(Order $order): Order
    {
        if ($order->fulfillment_status === FulfillmentStatus::Fulfilled) {
            throw new \RuntimeException('Cannot cancel a fully fulfilled order.');
        }

        /** @var Order $result */
        $result = DB::transaction(function () use ($order) {
            // Release reserved inventory
            $order->loadMissing('lines.variant.inventoryItem');
            foreach ($order->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                }
            }

            $order->update([
                'status' => OrderStatus::Cancelled,
                'financial_status' => FinancialStatus::Voided,
            ]);

            // Mark payments as failed
            $order->payments()->where('status', PaymentStatus::Pending->value)->update([
                'status' => PaymentStatus::Failed,
            ]);

            OrderCancelled::dispatch($order->refresh());

            Log::channel('json')->info('Order cancelled', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'store_id' => $order->store_id,
            ]);

            return $order;
        });

        return $result;
    }

    /**
     * Auto-fulfill digital products when all lines are non-shipping.
     */
    public function autoFulfillDigitalProducts(Order $order): void
    {
        $order->loadMissing('lines.variant');

        $allDigital = $order->lines->every(function (OrderLine $line) {
            return $line->variant && ! $line->variant->requires_shipping;
        });

        if (! $allDigital || $order->lines->isEmpty()) {
            return;
        }

        /** @var Fulfillment $fulfillment */
        $fulfillment = Fulfillment::query()->create([
            'order_id' => $order->id,
            'status' => FulfillmentShipmentStatus::Delivered,
            'shipped_at' => now(),
            'created_at' => now(),
        ]);

        foreach ($order->lines as $line) {
            FulfillmentLine::query()->create([
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
