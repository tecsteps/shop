<?php

namespace App\Http\Resources\Api\Storefront;

use App\Services\ShippingCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totals = (array) ($this->totals_json ?? []);
        $cart = $this->cart;
        $lines = $cart !== null ? $cart->cartLines : collect();

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'cart_id' => $this->cart_id,
            'customer_id' => $this->customer_id,
            'status' => $this->status->value,
            'email' => $this->email,
            'shipping_address_json' => $this->shipping_address_json,
            'billing_address_json' => $this->billing_address_json,
            'shipping_method_id' => $this->shipping_method_id,
            'payment_method' => $this->payment_method?->value,
            'discount_code' => $this->discount_code,
            'lines' => $lines->map(fn ($line): array => [
                'variant_id' => $line->variant_id,
                'product_title' => $line->variant->product->title,
                'variant_title' => $this->variantTitle($line->variant),
                'sku' => $line->variant->sku,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'line_total_amount' => $line->line_total_amount,
            ])->values()->all(),
            'totals' => [
                'subtotal' => (int) ($totals['subtotal'] ?? 0),
                'discount' => (int) ($totals['discount'] ?? 0),
                'shipping' => (int) ($totals['shipping'] ?? 0),
                'tax' => (int) ($totals['tax'] ?? 0),
                'total' => (int) ($totals['total'] ?? 0),
                'currency' => $totals['currency'] ?? $cart?->currency,
            ],
            'applied_discounts' => $totals['applied_discounts'] ?? [],
            'available_shipping_methods' => $this->availableShippingMethods(),
            'tax_provider_snapshot_json' => $this->tax_provider_snapshot_json,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function availableShippingMethods(): array
    {
        if ($this->shipping_address_json === null || $this->cart === null) {
            return [];
        }

        $options = app(ShippingCalculator::class)->getAvailableRates(
            $this->store,
            (array) $this->shipping_address_json,
            $this->cart,
        );

        return $options->map(fn ($option): array => [
            'id' => $option->id,
            'name' => $option->name,
            'type' => $option->type,
            'price_amount' => $option->amount,
            'currency' => $this->cart->currency,
            'estimated_days_min' => null,
            'estimated_days_max' => null,
        ])->all();
    }

    protected function variantTitle(mixed $variant): string
    {
        $labels = $variant->optionValues->pluck('value')->all();

        return $labels === [] ? 'Default' : implode(' / ', $labels);
    }
}
