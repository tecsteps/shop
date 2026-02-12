<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Exceptions\DiscountValidationException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DiscountService
{
    public function findCode(Store $store, string $code): ?Discount
    {
        return Discount::query()
            ->where('store_id', $store->id)
            ->whereRaw('lower(code) = ?', [mb_strtolower(trim($code))])
            ->first();
    }

    /**
     * @return array{discount: Discount, qualifying_lines: Collection<int, CartLine>}
     */
    public function validateCode(Cart $cart, string $code): array
    {
        $cart->loadMissing('store', 'lines.variant.product.collections');

        $discount = $this->findCode($cart->store, $code);

        if (! $discount) {
            throw new DiscountValidationException('discount_not_found', 'Discount code not found.', 422);
        }

        if ($discount->status !== DiscountStatus::Active) {
            throw new DiscountValidationException('discount_expired', 'This discount code has expired.', 400);
        }

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            throw new DiscountValidationException('discount_not_yet_active', 'This discount code is not active yet.', 422);
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            throw new DiscountValidationException('discount_expired', 'This discount code has expired.', 400);
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new DiscountValidationException('discount_usage_limit_reached', 'This discount code reached its usage limit.', 400);
        }

        $cartSubtotal = (int) $cart->lines->sum('line_subtotal_amount');
        $minPurchase = (int) ($discount->rules_json['min_purchase_amount'] ?? 0);

        if ($minPurchase > 0 && $cartSubtotal < $minPurchase) {
            throw new DiscountValidationException('discount_min_purchase_not_met', 'Minimum purchase amount not met.', 422);
        }

        $qualifyingLines = $this->qualifyingLines($discount, $cart);

        if ($this->hasScopedRules($discount) && $qualifyingLines->isEmpty()) {
            throw new DiscountValidationException('discount_not_applicable', 'This discount is not applicable to cart items.', 422);
        }

        return [
            'discount' => $discount,
            'qualifying_lines' => $qualifyingLines,
        ];
    }

    /**
     * @return array{discount: Discount, applied_amount: int, free_shipping: bool}
     */
    public function applyCodeToCart(Cart $cart, string $code): array
    {
        return DB::transaction(function () use ($cart, $code): array {
            $cart = Cart::query()->with(['lines.variant.product.collections', 'store'])->findOrFail($cart->id);
            $validated = $this->validateCode($cart, $code);

            /** @var Discount $discount */
            $discount = $validated['discount'];
            /** @var Collection<int, CartLine> $qualifyingLines */
            $qualifyingLines = $validated['qualifying_lines'];

            foreach ($cart->lines as $line) {
                $line->line_discount_amount = 0;
                $line->line_total_amount = $line->line_subtotal_amount;
                $line->save();
            }

            if ($discount->value_type === DiscountValueType::FreeShipping) {
                return [
                    'discount' => $discount,
                    'applied_amount' => 0,
                    'free_shipping' => true,
                ];
            }

            $qualifyingSubtotal = (int) $qualifyingLines->sum('line_subtotal_amount');

            if ($qualifyingSubtotal <= 0) {
                return [
                    'discount' => $discount,
                    'applied_amount' => 0,
                    'free_shipping' => false,
                ];
            }

            $discountTotal = $this->calculateDiscountTotal($discount, $qualifyingSubtotal);
            $remaining = $discountTotal;
            $count = $qualifyingLines->count();

            foreach ($qualifyingLines->values() as $index => $line) {
                if ($index === $count - 1) {
                    $lineDiscount = $remaining;
                } else {
                    $ratio = $line->line_subtotal_amount / $qualifyingSubtotal;
                    $lineDiscount = (int) round($discountTotal * $ratio);
                    $lineDiscount = min($lineDiscount, $remaining);
                }

                $lineDiscount = max(0, min($lineDiscount, $line->line_subtotal_amount));
                $remaining -= $lineDiscount;

                $line->line_discount_amount = $lineDiscount;
                $line->line_total_amount = $line->line_subtotal_amount - $lineDiscount;
                $line->save();
            }

            return [
                'discount' => $discount,
                'applied_amount' => $discountTotal,
                'free_shipping' => false,
            ];
        });
    }

    public function clearCartDiscount(Cart $cart): void
    {
        DB::transaction(function () use ($cart): void {
            $cart = Cart::query()->with('lines')->findOrFail($cart->id);

            foreach ($cart->lines as $line) {
                $line->line_discount_amount = 0;
                $line->line_total_amount = $line->line_subtotal_amount;
                $line->save();
            }
        });
    }

    public function findAppliedDiscount(Cart $cart, ?string $code): ?Discount
    {
        if (! $code) {
            return null;
        }

        return $this->findCode($cart->store, $code);
    }

    private function calculateDiscountTotal(Discount $discount, int $qualifyingSubtotal): int
    {
        return match ($discount->value_type) {
            DiscountValueType::Percent => (int) round($qualifyingSubtotal * ($discount->value_amount / 100)),
            DiscountValueType::Fixed => min($discount->value_amount, $qualifyingSubtotal),
            default => 0,
        };
    }

    /**
     * @return Collection<int, CartLine>
     */
    private function qualifyingLines(Discount $discount, Cart $cart): Collection
    {
        $productIds = collect((array) ($discount->rules_json['applicable_product_ids'] ?? []))
            ->filter(static fn ($value): bool => is_numeric($value))
            ->map(static fn ($value): int => (int) $value)
            ->values()
            ->all();

        $collectionIds = collect((array) ($discount->rules_json['applicable_collection_ids'] ?? []))
            ->filter(static fn ($value): bool => is_numeric($value))
            ->map(static fn ($value): int => (int) $value)
            ->values()
            ->all();

        if (empty($productIds) && empty($collectionIds)) {
            return $cart->lines;
        }

        return $cart->lines->filter(function (CartLine $line) use ($productIds, $collectionIds): bool {
            $productId = $line->variant->product_id;

            if (in_array($productId, $productIds, true)) {
                return true;
            }

            if (empty($collectionIds)) {
                return false;
            }

            $lineCollectionIds = $line->variant->product->collections->pluck('id')->all();

            return count(array_intersect($collectionIds, $lineCollectionIds)) > 0;
        })->values();
    }

    private function hasScopedRules(Discount $discount): bool
    {
        $productIds = (array) ($discount->rules_json['applicable_product_ids'] ?? []);
        $collectionIds = (array) ($discount->rules_json['applicable_collection_ids'] ?? []);

        return ! empty($productIds) || ! empty($collectionIds);
    }
}
