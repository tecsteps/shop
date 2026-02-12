<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use App\Models\StoreSettings;

class OrderService
{
    /**
     * @param  array<string, mixed>  $totals
     */
    public function createFromCheckout(
        Checkout $checkout,
        array $totals,
        PaymentMethod $paymentMethod,
        OrderStatus $orderStatus,
        FinancialStatus $financialStatus,
    ): Order {
        $checkout->loadMissing('cart', 'store');

        return Order::query()->create([
            'store_id' => $checkout->store_id,
            'customer_id' => $checkout->customer_id,
            'order_number' => $this->generateNextOrderNumber($checkout->store),
            'payment_method' => $paymentMethod,
            'status' => $orderStatus,
            'financial_status' => $financialStatus,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'currency' => $checkout->cart->currency,
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
    }

    /**
     * @param  array<int, int>  $lineTaxes
     */
    public function createLinesFromCart(
        Order $order,
        Cart $cart,
        ?Discount $discount = null,
        array $lineTaxes = [],
    ): void {
        $cart->loadMissing('lines.variant.optionValues.option', 'lines.variant.product');

        foreach ($cart->lines as $cartLine) {
            $variant = $cartLine->variant;
            $product = $variant->product;

            $variantLabel = $variant->optionValues
                ->sortBy(fn ($value) => $value->option->position)
                ->pluck('value')
                ->implode(' / ');

            $titleSnapshot = $product->title;

            if ($variantLabel !== '') {
                $titleSnapshot .= ' - '.$variantLabel;
            }

            $discountAllocations = [];

            if ($cartLine->line_discount_amount > 0) {
                $discountAllocations[] = [
                    'discount_id' => $discount?->id,
                    'code' => $discount?->code,
                    'amount' => (int) $cartLine->line_discount_amount,
                ];
            }

            $taxLines = [];

            if (array_key_exists($cartLine->id, $lineTaxes)) {
                $taxLines[] = [
                    'rate' => null,
                    'amount' => (int) $lineTaxes[$cartLine->id],
                ];
            }

            OrderLine::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'title_snapshot' => $titleSnapshot,
                'sku_snapshot' => $variant->sku,
                'quantity' => $cartLine->quantity,
                'unit_price_amount' => $cartLine->unit_price_amount,
                'total_amount' => $cartLine->line_total_amount,
                'tax_lines_json' => $taxLines,
                'discount_allocations_json' => $discountAllocations,
            ]);
        }
    }

    public function generateNextOrderNumber(Store $store): string
    {
        $prefix = $this->resolveOrderNumberPrefix($store);

        $maxNumber = Order::query()
            ->where('store_id', $store->id)
            ->pluck('order_number')
            ->map(static fn (string $number): int => (int) preg_replace('/\D+/', '', $number))
            ->max();

        $next = max(1000, (int) $maxNumber) + 1;

        return $prefix.$next;
    }

    private function resolveOrderNumberPrefix(Store $store): string
    {
        $settings = StoreSettings::query()->where('store_id', $store->id)->first();

        return (string) ($settings?->settings_json['order_number_prefix'] ?? '#');
    }
}
