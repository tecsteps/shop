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
use App\Events\CheckoutCompleted;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Exceptions\PaymentFailedException;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected PaymentProvider $paymentProvider,
        protected InventoryService $inventoryService,
    ) {}

    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function completeCheckout(Checkout $checkout, array $paymentMethodData): Order
    {
        $existingOrder = Order::query()
            ->withoutGlobalScopes()
            ->whereHas('payments', function ($q) use ($checkout) {
                $q->where('order_id', '>', 0);
            })
            ->where('store_id', $checkout->store_id)
            ->where('email', $checkout->email)
            ->whereHas('lines', function ($q) use ($checkout) {
                $cart = $checkout->cart;
                if ($cart) {
                    $q->whereIn('variant_id', $cart->lines()->pluck('variant_id'));
                }
            })
            ->first();

        // Idempotency: check if checkout is already completed
        if ($checkout->status === CheckoutStatus::Completed) {
            $order = Order::query()
                ->withoutGlobalScopes()
                ->where('store_id', $checkout->store_id)
                ->where('email', $checkout->email)
                ->latest()
                ->first();

            if ($order) {
                return $order;
            }
        }

        return DB::transaction(function () use ($checkout, $paymentMethodData) {
            // 1. Charge payment
            $paymentResult = $this->paymentProvider->charge($checkout, $paymentMethodData);

            if (! $paymentResult->success) {
                // Release reserved inventory on payment failure
                $this->releaseReservedInventory($checkout);

                throw new PaymentFailedException(
                    $paymentResult->errorCode ?? 'unknown',
                    $paymentResult->errorMessage,
                );
            }

            // 2. Determine statuses based on payment method
            $method = $checkout->payment_method;
            $isInstantCapture = in_array($method, [PaymentMethod::CreditCard, PaymentMethod::Paypal]);

            $orderStatus = $isInstantCapture ? OrderStatus::Paid : OrderStatus::Pending;
            $financialStatus = $isInstantCapture ? FinancialStatus::Paid : FinancialStatus::Pending;
            $paymentStatus = $paymentResult->status;

            // 3. Generate order number
            $orderNumber = $this->generateOrderNumber($checkout->store_id);

            // 4. Get totals from checkout
            $totals = $checkout->totals_json ?? [];

            // 5. Create order
            $order = Order::query()->create([
                'store_id' => $checkout->store_id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $orderNumber,
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
                'placed_at' => now(),
            ]);

            // 6. Create order lines from cart lines
            $cart = $checkout->cart()->with('lines.variant.product')->first();
            $allDigital = true;

            foreach ($cart->lines as $cartLine) {
                $variant = $cartLine->variant;
                $product = $variant?->product;

                $titleSnapshot = $product?->title ?? 'Unknown Product';
                if ($variant?->title) {
                    $titleSnapshot .= ' - ' . $variant->title;
                }

                OrderLine::query()->create([
                    'order_id' => $order->id,
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

                if ($variant && $variant->requires_shipping) {
                    $allDigital = false;
                }
            }

            // 7. Create payment record
            Payment::query()->create([
                'order_id' => $order->id,
                'provider' => 'mock',
                'method' => $method,
                'provider_payment_id' => $paymentResult->providerPaymentId,
                'status' => $paymentStatus,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'raw_json_encrypted' => Crypt::encryptString(json_encode($paymentResult->rawResponse)),
            ]);

            // 8. Handle inventory
            if ($isInstantCapture) {
                foreach ($cart->lines as $cartLine) {
                    if (! $cartLine->variant) {
                        continue;
                    }

                    $inventoryItem = InventoryItem::query()
                        ->withoutGlobalScopes()
                        ->where('variant_id', $cartLine->variant_id)
                        ->first();

                    if ($inventoryItem) {
                        $this->inventoryService->commit($inventoryItem, $cartLine->quantity);
                    }
                }
            }
            // For bank_transfer, inventory stays reserved until admin confirms

            // 9. Increment discount usage
            if ($checkout->discount_code) {
                Discount::query()
                    ->withoutGlobalScopes()
                    ->where('store_id', $checkout->store_id)
                    ->whereRaw('LOWER(code) = ?', [strtolower($checkout->discount_code)])
                    ->increment('usage_count');
            }

            // 10. Mark cart as converted
            $cart->update(['status' => CartStatus::Converted]);

            // 11. Mark checkout as completed
            $checkout->update(['status' => CheckoutStatus::Completed]);

            // 12. Auto-fulfill digital orders
            if ($isInstantCapture && $allDigital) {
                $this->autoFulfillDigitalOrder($order);
            }

            // 13. Dispatch events
            OrderCreated::dispatch($order);
            CheckoutCompleted::dispatch($checkout);

            return $order;
        });
    }

    public function generateOrderNumber(int $storeId): string
    {
        $maxNumber = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->max(DB::raw("CAST(REPLACE(order_number, '#', '') AS INTEGER)"));

        $nextNumber = $maxNumber ? $maxNumber + 1 : 1001;

        return '#' . $nextNumber;
    }

    public function cancel(Order $order, string $reason = ''): void
    {
        if ($order->fulfillment_status !== FulfillmentStatus::Unfulfilled) {
            throw new \RuntimeException('Cannot cancel an order that has been partially or fully fulfilled.');
        }

        DB::transaction(function () use ($order) {
            // Release inventory
            $order->load('lines.variant');

            foreach ($order->lines as $line) {
                if (! $line->variant) {
                    continue;
                }

                $inventoryItem = InventoryItem::query()
                    ->withoutGlobalScopes()
                    ->where('variant_id', $line->variant_id)
                    ->first();

                if ($inventoryItem) {
                    if ($order->financial_status === FinancialStatus::Pending) {
                        // Bank transfer: release reserved inventory
                        $this->inventoryService->release($inventoryItem, $line->quantity);
                    } else {
                        // Paid orders: restock
                        $this->inventoryService->restock($inventoryItem, $line->quantity);
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
            $order->payments()
                ->where('status', PaymentStatus::Pending)
                ->update(['status' => PaymentStatus::Failed]);

            OrderCancelled::dispatch($order);
        });
    }

    public function confirmBankTransferPayment(Order $order): void
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer) {
            throw new \RuntimeException('This order does not use bank transfer payment.');
        }

        if ($order->financial_status !== FinancialStatus::Pending) {
            throw new \RuntimeException('Payment has already been processed for this order.');
        }

        DB::transaction(function () use ($order) {
            // Update payment status
            $order->payments()
                ->where('status', PaymentStatus::Pending)
                ->update(['status' => PaymentStatus::Captured]);

            // Update order status
            $order->update([
                'financial_status' => FinancialStatus::Paid,
                'status' => OrderStatus::Paid,
            ]);

            // Commit reserved inventory
            $order->load('lines.variant');

            foreach ($order->lines as $line) {
                if (! $line->variant) {
                    continue;
                }

                $inventoryItem = InventoryItem::query()
                    ->withoutGlobalScopes()
                    ->where('variant_id', $line->variant_id)
                    ->first();

                if ($inventoryItem) {
                    $this->inventoryService->commit($inventoryItem, $line->quantity);
                }
            }

            // Auto-fulfill if all digital
            $allDigital = true;
            foreach ($order->lines as $line) {
                if ($line->variant && $line->variant->requires_shipping) {
                    $allDigital = false;
                    break;
                }
            }

            if ($allDigital) {
                $this->autoFulfillDigitalOrder($order);
            }

            \App\Events\OrderPaid::dispatch($order);
        });
    }

    protected function autoFulfillDigitalOrder(Order $order): void
    {
        $order->load('lines');

        $fulfillment = Fulfillment::query()->create([
            'order_id' => $order->id,
            'status' => FulfillmentShipmentStatus::Delivered,
            'shipped_at' => now(),
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

    protected function releaseReservedInventory(Checkout $checkout): void
    {
        $cart = $checkout->cart()->with('lines.variant')->first();

        foreach ($cart->lines as $line) {
            if (! $line->variant) {
                continue;
            }

            $inventoryItem = InventoryItem::query()
                ->withoutGlobalScopes()
                ->where('variant_id', $line->variant_id)
                ->first();

            if ($inventoryItem) {
                $this->inventoryService->release($inventoryItem, $line->quantity);
            }
        }
    }
}
