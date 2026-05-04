<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\PaymentFailedException;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ValueError;

class OrderService
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly InventoryService $inventory,
        private readonly PricingEngine $pricing,
    ) {}

    /**
     * Create an order from a checkout after payment has been selected.
     *
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function createFromCheckout(Checkout $checkout, array $paymentMethodData = []): Order
    {
        try {
            return DB::transaction(function () use ($checkout, $paymentMethodData): Order {
                $checkout = $this->freshCheckout($checkout);
                $existingOrder = Order::withoutGlobalScopes()
                    ->where('checkout_id', $checkout->getKey())
                    ->first();

                if ($existingOrder instanceof Order) {
                    return $existingOrder->load(['lines', 'payments', 'refunds', 'fulfillments.lines']);
                }

                if ($checkout->status !== CheckoutStatus::PaymentSelected) {
                    throw InvalidCheckoutTransitionException::because("Checkout cannot transition from {$checkout->status->value}.");
                }

                if ($checkout->totals_json === null) {
                    $this->pricing->calculate($checkout);
                    $checkout = $this->freshCheckout($checkout);
                }

                $method = $this->paymentMethod($checkout);
                $paymentResult = $this->payments->charge($checkout, $method, $paymentMethodData);

                if (! $paymentResult->success) {
                    throw PaymentFailedException::fromResult($paymentResult);
                }

                $paidImmediately = $paymentResult->status === PaymentStatus::Captured;
                $totals = $checkout->totals_json ?? [];

                $order = Order::withoutGlobalScopes()->create([
                    'store_id' => $checkout->store_id,
                    'checkout_id' => $checkout->getKey(),
                    'customer_id' => $checkout->customer_id,
                    'order_number' => $this->nextOrderNumber($checkout->store),
                    'payment_method' => $method,
                    'status' => $paidImmediately ? OrderStatus::Paid : OrderStatus::Pending,
                    'financial_status' => $paidImmediately ? FinancialStatus::Paid : FinancialStatus::Pending,
                    'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                    'currency' => (string) data_get($totals, 'currency', $checkout->cart->currency),
                    'subtotal_amount' => (int) data_get($totals, 'subtotal', 0),
                    'discount_amount' => (int) data_get($totals, 'discount', 0),
                    'shipping_amount' => (int) data_get($totals, 'shipping', 0),
                    'tax_amount' => (int) data_get($totals, 'tax', 0),
                    'total_amount' => (int) data_get($totals, 'total', 0),
                    'email' => $checkout->email,
                    'billing_address_json' => $checkout->billing_address_json,
                    'shipping_address_json' => $checkout->shipping_address_json,
                    'placed_at' => now(),
                ]);

                $this->createOrderLines($checkout, $order);

                $order->payments()->create([
                    'provider' => 'mock',
                    'method' => $method,
                    'provider_payment_id' => $paymentResult->referenceId,
                    'status' => $paymentResult->status,
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'raw_json_encrypted' => $paymentResult->toArray(),
                ]);

                if ($paidImmediately) {
                    $this->commitReservedInventory($checkout);
                }

                $checkout->cart->forceFill([
                    'status' => CartStatus::Converted,
                ])->save();

                $checkout->forceFill([
                    'status' => CheckoutStatus::Completed,
                    'expires_at' => null,
                ])->save();

                $this->incrementDiscountUsage($checkout);

                $order = $order->refresh()->load(['lines', 'payments', 'refunds', 'fulfillments.lines']);

                event(new OrderCreated($order));

                if ($paidImmediately) {
                    event(new OrderPaid($order));
                }

                return $order;
            });
        } catch (PaymentFailedException $exception) {
            $this->releaseFailedPaymentReservation($checkout);

            throw $exception;
        }
    }

    private function freshCheckout(Checkout $checkout): Checkout
    {
        return Checkout::withoutGlobalScopes()
            ->with(['cart', 'store'])
            ->whereKey($checkout->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function paymentMethod(Checkout $checkout): PaymentMethod
    {
        try {
            return PaymentMethod::from((string) $checkout->payment_method);
        } catch (ValueError) {
            throw InvalidCheckoutTransitionException::because('Payment method is invalid.');
        }
    }

    private function nextOrderNumber(Store $store): string
    {
        $settings = StoreSettings::query()
            ->where('store_id', $store->getKey())
            ->first()
            ?->settings_json ?? [];
        $prefix = (string) data_get($settings, 'order_number_prefix', '#');
        $start = max(1, (int) data_get($settings, 'order_number_start', 1001));
        $maxExisting = Order::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->pluck('order_number')
            ->map(fn (string $orderNumber): ?int => $this->sequenceFromOrderNumber($orderNumber))
            ->filter()
            ->max();

        return $prefix.((int) max($start - 1, $maxExisting ?? 0) + 1);
    }

    private function sequenceFromOrderNumber(string $orderNumber): ?int
    {
        $digits = preg_replace('/\D+/', '', $orderNumber);

        return $digits === '' ? null : (int) $digits;
    }

    private function createOrderLines(Checkout $checkout, Order $order): void
    {
        $this->cartLines($checkout)->each(function (CartLine $line) use ($checkout, $order): void {
            $variant = $line->variant;

            $order->lines()->create([
                'product_id' => $variant?->product_id,
                'variant_id' => $variant?->getKey(),
                'title_snapshot' => $this->titleSnapshot($variant),
                'sku_snapshot' => $variant?->sku,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'total_amount' => $line->line_total_amount,
                'tax_lines_json' => [],
                'discount_allocations_json' => $this->discountAllocations($checkout, $line),
            ]);
        });
    }

    private function titleSnapshot(?ProductVariant $variant): string
    {
        $title = $variant?->product?->title ?? 'Product';
        $optionValues = $variant?->optionValues
            ->sortBy(fn (ProductOptionValue $value): int => $value->option?->position ?? $value->position)
            ->pluck('value')
            ->filter()
            ->implode(' / ');

        return $optionValues ? "{$title} - {$optionValues}" : $title;
    }

    /**
     * @return array<int, array{code: string, amount: int}>
     */
    private function discountAllocations(Checkout $checkout, CartLine $line): array
    {
        if ($checkout->discount_code === null || $line->line_discount_amount <= 0) {
            return [];
        }

        return [[
            'code' => $checkout->discount_code,
            'amount' => $line->line_discount_amount,
        ]];
    }

    private function commitReservedInventory(Checkout $checkout): void
    {
        $this->cartLines($checkout)->each(function (CartLine $line): void {
            $this->inventory->commit($this->inventoryItem($line), $line->quantity);
        });
    }

    private function releaseFailedPaymentReservation(Checkout $checkout): void
    {
        DB::transaction(function () use ($checkout): void {
            $checkout = $this->freshCheckout($checkout);

            if ($checkout->status !== CheckoutStatus::PaymentSelected) {
                return;
            }

            $this->cartLines($checkout)->each(function (CartLine $line): void {
                $this->inventory->release($this->inventoryItem($line), $line->quantity);
            });

            $checkout->forceFill([
                'status' => CheckoutStatus::ShippingSelected,
                'payment_method' => null,
                'expires_at' => null,
            ])->save();
        });
    }

    private function incrementDiscountUsage(Checkout $checkout): void
    {
        $code = trim((string) $checkout->discount_code);

        if ($code === '') {
            return;
        }

        Discount::withoutGlobalScopes()
            ->where('store_id', $checkout->store_id)
            ->whereRaw('lower(code) = ?', [mb_strtolower($code)])
            ->increment('usage_count');
    }

    private function inventoryItem(CartLine $line): InventoryItem
    {
        return InventoryItem::withoutGlobalScopes()
            ->where('variant_id', $line->variant_id)
            ->firstOrFail();
    }

    /**
     * @return Collection<int, CartLine>
     */
    private function cartLines(Checkout $checkout): Collection
    {
        return CartLine::withoutGlobalScopes()
            ->with([
                'variant' => fn ($query) => $query->withoutGlobalScopes(),
                'variant.product' => fn ($query) => $query->withoutGlobalScopes(),
                'variant.optionValues' => fn ($query) => $query->withoutGlobalScopes(),
                'variant.optionValues.option' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->where('cart_id', $checkout->cart_id)
            ->orderBy('id')
            ->get();
    }
}
