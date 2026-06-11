<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Store;
use App\ValueObjects\PaymentResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Order numbers are sequential per store starting at 1001 (spec 05
     * section 11.2).
     */
    public const int FIRST_ORDER_NUMBER = 1001;

    public function __construct(
        protected InventoryService $inventoryService,
        protected FulfillmentService $fulfillmentService,
    ) {}

    /**
     * Create the order from a checkout whose payment was just processed.
     * Atomic: order + snapshot lines + payment record, inventory commit for
     * captured payments (bank transfer stays reserved), discount usage
     * increment, cart conversion, and the OrderCreated event (spec 05
     * section 6.2 steps 3-13).
     */
    public function createFromCheckout(Checkout $checkout, PaymentResult $paymentResult): Order
    {
        return DB::transaction(function () use ($checkout, $paymentResult): Order {
            $cart = Cart::query()->withoutGlobalScopes()->findOrFail($checkout->cart_id);
            $cartLines = CartLine::query()
                ->where('cart_id', $cart->getKey())
                ->with(['variant.product', 'variant.optionValues', 'variant.inventoryItem'])
                ->get();

            $totals = $checkout->totals_json ?? [];
            $captured = $paymentResult->status === PaymentStatus::Captured;

            $order = Order::query()->create([
                'store_id' => $checkout->store_id,
                'customer_id' => $checkout->customer_id,
                'checkout_id' => $checkout->getKey(),
                'order_number' => $this->generateOrderNumber($checkout->store),
                'payment_method' => $checkout->payment_method,
                'status' => $captured ? OrderStatus::Paid : OrderStatus::Pending,
                'financial_status' => $captured ? FinancialStatus::Paid : FinancialStatus::Pending,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'currency' => $totals['currency'] ?? $cart->currency,
                'subtotal_amount' => $totals['subtotal'] ?? 0,
                'discount_amount' => $totals['discount'] ?? 0,
                'shipping_amount' => $totals['shipping'] ?? 0,
                'tax_amount' => $totals['tax'] ?? 0,
                'total_amount' => $totals['total'] ?? 0,
                'email' => $checkout->email,
                'billing_address_json' => $checkout->billing_address_json,
                'shipping_address_json' => $checkout->shipping_address_json,
                'placed_at' => now(),
            ]);

            $discount = $this->resolveDiscount($checkout);

            foreach ($cartLines as $cartLine) {
                $this->createOrderLine($order, $cartLine, $discount);
            }

            Payment::query()->create([
                'order_id' => $order->getKey(),
                'provider' => 'mock',
                'method' => $checkout->payment_method,
                'provider_payment_id' => $paymentResult->providerPaymentId,
                'status' => $paymentResult->status,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'raw_json_encrypted' => $paymentResult->raw,
            ]);

            if ($captured) {
                $this->commitInventory($cartLines);
            }

            $discount?->increment('usage_count');

            $cart->forceFill(['status' => CartStatus::Converted])->save();

            event(new OrderCreated($order));

            if ($captured) {
                $this->fulfillmentService->autoFulfillDigital($order);
            }

            return $order->refresh();
        });
    }

    /**
     * Next sequential order number for the store, e.g. #1001, #1002. Runs
     * inside the order creation transaction (SQLite serializes writers) and
     * the unique (store_id, order_number) index backstops duplicates.
     */
    public function generateOrderNumber(Store $store): string
    {
        $prefix = $store->settings?->settings_json['order_number_prefix'] ?? '#';

        $highest = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->pluck('order_number')
            ->map(fn (string $number): int => (int) preg_replace('/\D/', '', $number))
            ->max();

        $next = max(($highest ?? 0) + 1, self::FIRST_ORDER_NUMBER);

        return $prefix.$next;
    }

    /**
     * Cancel an unfulfilled order. Pending (bank transfer) orders release
     * their reservation and void the payment; paid orders restock committed
     * inventory (spec 05 sections 10.8 and 11.3).
     *
     * @throws ValidationException
     */
    public function cancel(Order $order, ?string $reason = null): void
    {
        if ($order->status === OrderStatus::Cancelled) {
            return;
        }

        if ($order->fulfillment_status !== FulfillmentStatus::Unfulfilled) {
            throw ValidationException::withMessages([
                'order' => __('Only unfulfilled orders can be cancelled.'),
            ]);
        }

        DB::transaction(function () use ($order): void {
            $wasPending = $order->financial_status === FinancialStatus::Pending;

            foreach ($order->lines()->with('variant.inventoryItem')->get() as $line) {
                $item = $line->variant?->inventoryItem;

                if ($item === null) {
                    continue;
                }

                if ($wasPending) {
                    $this->inventoryService->release($item, $line->quantity);
                } elseif ($order->financial_status === FinancialStatus::Paid) {
                    $this->inventoryService->restock($item, $line->quantity);
                }
            }

            if ($wasPending) {
                $order->payments()
                    ->where('status', PaymentStatus::Pending)
                    ->update(['status' => PaymentStatus::Failed]);

                $order->forceFill(['financial_status' => FinancialStatus::Voided]);
            }

            $order->forceFill(['status' => OrderStatus::Cancelled])->save();

            event(new OrderCancelled($order));
        });
    }

    /**
     * Admin "Confirm Payment" action for bank transfer orders (spec 05
     * section 10.7): capture the payment, mark the order paid, commit the
     * reserved inventory, and auto-fulfill fully digital orders.
     *
     * @throws ValidationException
     */
    public function confirmBankTransferPayment(Order $order): Order
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer) {
            throw ValidationException::withMessages([
                'order' => __('Only bank transfer orders can be confirmed manually.'),
            ]);
        }

        if ($order->financial_status !== FinancialStatus::Pending) {
            throw ValidationException::withMessages([
                'order' => __('This order\'s payment has already been confirmed.'),
            ]);
        }

        return DB::transaction(function () use ($order): Order {
            $order->payments()
                ->where('status', PaymentStatus::Pending)
                ->update(['status' => PaymentStatus::Captured]);

            $order->forceFill([
                'financial_status' => FinancialStatus::Paid,
                'status' => OrderStatus::Paid,
            ])->save();

            foreach ($order->lines()->with('variant.inventoryItem')->get() as $line) {
                if ($line->variant?->inventoryItem !== null) {
                    $this->inventoryService->commit($line->variant->inventoryItem, $line->quantity);
                }
            }

            $this->fulfillmentService->autoFulfillDigital($order);

            event(new OrderPaid($order));

            return $order->refresh();
        });
    }

    /**
     * Snapshot a cart line into an order line so order history survives
     * product and variant deletion.
     */
    protected function createOrderLine(Order $order, CartLine $cartLine, ?Discount $discount): OrderLine
    {
        $variant = $cartLine->variant;
        $product = $variant?->product;

        $title = $product?->title ?? __('Unavailable product');
        $optionLabels = $variant?->optionValues->pluck('value')->implode(' / ') ?? '';

        if ($optionLabels !== '') {
            $title .= " ({$optionLabels})";
        }

        $allocations = [];

        if ($discount !== null && $cartLine->line_discount_amount > 0) {
            $allocations[] = [
                'discount_id' => $discount->getKey(),
                'amount' => $cartLine->line_discount_amount,
            ];
        }

        return OrderLine::query()->create([
            'order_id' => $order->getKey(),
            'product_id' => $product?->getKey(),
            'variant_id' => $variant?->getKey(),
            'title_snapshot' => $title,
            'sku_snapshot' => $variant?->sku,
            'quantity' => $cartLine->quantity,
            'unit_price_amount' => $cartLine->unit_price_amount,
            'total_amount' => $cartLine->line_total_amount,
            'tax_lines_json' => [],
            'discount_allocations_json' => $allocations,
        ]);
    }

    /**
     * Convert the reservation into a committed sale for every tracked line.
     *
     * @param  \Illuminate\Support\Collection<int, CartLine>  $cartLines
     */
    protected function commitInventory($cartLines): void
    {
        foreach ($cartLines as $cartLine) {
            if ($cartLine->variant?->inventoryItem !== null) {
                $this->inventoryService->commit($cartLine->variant->inventoryItem, $cartLine->quantity);
            }
        }
    }

    /**
     * The discount applied to the checkout, if its code still exists.
     */
    protected function resolveDiscount(Checkout $checkout): ?Discount
    {
        if (blank($checkout->discount_code)) {
            return null;
        }

        return Discount::query()
            ->withoutGlobalScopes()
            ->where('store_id', $checkout->store_id)
            ->where('code', $checkout->discount_code)
            ->first();
    }
}
