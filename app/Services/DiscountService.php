<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class DiscountService
{
    /**
     * Validates a code against a store and (optionally) a cart.
     */
    public function validate(string $code, Store $store, ?Cart $cart = null): Discount
    {
        $normalized = trim($code);

        if ($normalized === '') {
            throw new InvalidDiscountException('not_found', 'Discount code is empty.');
        }

        $discount = Discount::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($normalized)])
            ->first();

        if (! $discount) {
            throw new InvalidDiscountException('not_found', 'Discount code is not valid.');
        }

        $now = now();

        if ($discount->status !== DiscountStatus::Active) {
            throw new InvalidDiscountException('expired', 'Discount is not active.');
        }

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            throw new InvalidDiscountException('not_yet_active', 'Discount is not yet active.');
        }

        if ($discount->ends_at && $discount->ends_at->lt($now)) {
            throw new InvalidDiscountException('expired', 'Discount has expired.');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new InvalidDiscountException('usage_limit_reached', 'Discount usage limit reached.');
        }

        if ($cart) {
            $rules = $discount->rules_json ?? [];
            $minimum = $rules['min_purchase_amount'] ?? null;
            $subtotal = (int) $cart->lines->sum('line_subtotal_amount');

            if ($minimum !== null && $subtotal < (int) $minimum) {
                throw new InvalidDiscountException('minimum_not_met', 'Minimum purchase amount not met.');
            }

            $qualifying = $this->qualifyingLines($discount, $cart->lines);
            if ($qualifying->isEmpty() && $this->hasItemRestrictions($discount)) {
                throw new InvalidDiscountException('not_applicable', 'No qualifying products in cart.');
            }
        }

        return $discount;
    }

    /**
     * Calculate the total discount and allocations across cart lines.
     *
     * @return array{total: int, allocations: array<int, int>}
     */
    public function calculate(Discount $discount, Cart $cart): array
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return ['total' => 0, 'allocations' => []];
        }

        $lines = $cart->lines;
        $qualifying = $this->qualifyingLines($discount, $lines);
        $qualifyingSubtotal = (int) $qualifying->sum('line_subtotal_amount');

        if ($qualifyingSubtotal <= 0) {
            return ['total' => 0, 'allocations' => []];
        }

        $total = match ($discount->value_type) {
            DiscountValueType::Percent => intdiv($qualifyingSubtotal * $discount->value_amount, 100),
            DiscountValueType::Fixed => min($discount->value_amount, $qualifyingSubtotal),
            default => 0,
        };

        if ($total <= 0) {
            return ['total' => 0, 'allocations' => []];
        }

        $allocations = [];
        $remaining = $total;
        $count = $qualifying->count();

        foreach ($qualifying->values() as $index => $line) {
            if ($index === $count - 1) {
                $allocations[$line->id] = $remaining;
            } else {
                $share = (int) round($total * $line->line_subtotal_amount / $qualifyingSubtotal);
                $allocations[$line->id] = $share;
                $remaining -= $share;
            }
        }

        return ['total' => $total, 'allocations' => $allocations];
    }

    public function applyAllocationsToCart(Cart $cart, array $allocations): void
    {
        DB::transaction(function () use ($cart, $allocations): void {
            foreach ($cart->lines as $line) {
                $amount = (int) ($allocations[$line->id] ?? 0);
                $line->line_discount_amount = $amount;
                $line->line_total_amount = $line->line_subtotal_amount - $amount;
                $line->save();
            }
        });
    }

    public function incrementUsage(Discount $discount): void
    {
        $discount->increment('usage_count');
    }

    protected function hasItemRestrictions(Discount $discount): bool
    {
        $rules = $discount->rules_json ?? [];
        $products = $rules['applicable_product_ids'] ?? null;
        $collections = $rules['applicable_collection_ids'] ?? null;

        return (is_array($products) && ! empty($products)) || (is_array($collections) && ! empty($collections));
    }

    /**
     * @param  iterable<int, CartLine>  $lines
     */
    protected function qualifyingLines(Discount $discount, $lines): \Illuminate\Support\Collection
    {
        $rules = $discount->rules_json ?? [];
        $products = $rules['applicable_product_ids'] ?? null;
        $collections = $rules['applicable_collection_ids'] ?? null;

        $collection = collect($lines);

        if (empty($products) && empty($collections)) {
            return $collection;
        }

        return $collection->filter(function (CartLine $line) use ($products, $collections): bool {
            $line->loadMissing('variant.product.collections');
            $product = $line->variant?->product;

            if (! $product) {
                return false;
            }

            if (! empty($products) && in_array($product->id, $products, true)) {
                return true;
            }

            if (! empty($collections)) {
                $ids = $product->collections->pluck('id')->all();
                foreach ($collections as $id) {
                    if (in_array($id, $ids, true)) {
                        return true;
                    }
                }
            }

            return false;
        })->values();
    }
}
