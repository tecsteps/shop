<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\OrderLine;
use App\Models\ProductVariant;
use App\Models\Store;
use App\ValueObjects\DiscountResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DiscountService
{
    public function validate(string $code, Store $store, Cart $cart): Discount
    {
        $normalizedCode = mb_strtolower(trim($code));

        $discount = Discount::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('type', DiscountType::Code)
            ->whereRaw('lower(code) = ?', [$normalizedCode])
            ->first();

        if (! $discount instanceof Discount) {
            throw InvalidDiscountException::because('discount_not_found', 'Invalid discount code.');
        }

        $this->validateDiscountForCart($discount, $cart);

        return $discount;
    }

    /**
     * @return Collection<int, Discount>
     */
    public function automaticForCart(Store $store, Cart $cart): Collection
    {
        return Discount::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('type', DiscountType::Automatic->value)
            ->where('status', DiscountStatus::Active->value)
            ->orderBy('id')
            ->get()
            ->filter(function (Discount $discount) use ($cart): bool {
                try {
                    $this->validateDiscountForCart($discount, $cart);

                    return true;
                } catch (InvalidDiscountException) {
                    return false;
                }
            })
            ->values();
    }

    private function validateDiscountForCart(Discount $discount, Cart $cart): void
    {
        if ($discount->status === DiscountStatus::Expired || ($discount->ends_at !== null && $discount->ends_at->isPast())) {
            throw InvalidDiscountException::because('discount_expired', 'Discount has expired.');
        }

        if ($discount->status !== DiscountStatus::Active) {
            throw InvalidDiscountException::because('discount_not_active', 'Discount is not active.');
        }

        if ($discount->starts_at->isFuture()) {
            throw InvalidDiscountException::because('discount_not_yet_active', 'Discount is not active yet.');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw InvalidDiscountException::because('discount_usage_limit_reached', 'Discount usage limit has been reached.');
        }

        if ($this->onePerCustomer($discount) && $this->customerHasUsedDiscount($discount, $cart)) {
            throw InvalidDiscountException::because('discount_usage_limit_reached', 'Discount has already been used by this customer.');
        }

        $lines = $this->cartLines($cart);
        $subtotal = $lines->sum('line_subtotal_amount');
        $minimum = (int) data_get($discount->rules_json, 'min_purchase_amount', data_get($discount->rules_json, 'minimum_purchase', 0));

        if ($minimum > 0 && $subtotal < $minimum) {
            throw InvalidDiscountException::because('discount_min_purchase_not_met', 'Cart does not meet the minimum purchase amount.');
        }

        if ($this->qualifyingLines($discount, $lines)->isEmpty()) {
            throw InvalidDiscountException::because('discount_not_applicable', 'Discount does not apply to these cart lines.');
        }
    }

    /**
     * @param  array<int, CartLine>  $lines
     */
    public function calculate(Discount $discount, int $subtotal, array $lines): DiscountResult
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return new DiscountResult(0, [], true);
        }

        $qualifyingLines = $this->qualifyingLines($discount, collect($lines));
        $qualifyingSubtotal = $qualifyingLines->sum('line_total_amount');

        if ($qualifyingSubtotal <= 0) {
            return new DiscountResult(0, []);
        }

        $discountAmount = match ($discount->value_type) {
            DiscountValueType::Percent => (int) round($qualifyingSubtotal * $discount->value_amount / 100),
            DiscountValueType::Fixed => min($discount->value_amount, $qualifyingSubtotal),
            DiscountValueType::FreeShipping => 0,
        };

        $remaining = $discountAmount;
        $allocations = [];
        $lastIndex = $qualifyingLines->keys()->last();

        foreach ($qualifyingLines as $index => $line) {
            if ($index === $lastIndex) {
                $allocations[$line->getKey()] = $remaining;

                continue;
            }

            $allocation = (int) round($discountAmount * $line->line_total_amount / $qualifyingSubtotal);
            $allocations[$line->getKey()] = $allocation;
            $remaining -= $allocation;
        }

        return new DiscountResult($discountAmount, $allocations);
    }

    public function applyToCart(Cart $cart, Discount $discount): DiscountResult
    {
        return DB::transaction(function () use ($cart, $discount): DiscountResult {
            $lines = $this->cartLines($cart);
            $result = $this->calculate($discount, $lines->sum('line_subtotal_amount'), $lines->all());

            $lines->each(function (CartLine $line) use ($result): void {
                $discountAmount = $result->allocations[$line->getKey()] ?? 0;

                $line->forceFill([
                    'line_discount_amount' => $line->line_discount_amount + $discountAmount,
                    'line_total_amount' => max(0, $line->line_total_amount - $discountAmount),
                ])->save();
            });

            return $result;
        });
    }

    /**
     * @return Collection<int, CartLine>
     */
    private function cartLines(Cart $cart): Collection
    {
        return CartLine::withoutGlobalScopes()
            ->where('cart_id', $cart->getKey())
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, CartLine>  $lines
     * @return Collection<int, CartLine>
     */
    private function qualifyingLines(Discount $discount, Collection $lines): Collection
    {
        $productIds = collect(data_get($discount->rules_json, 'applicable_product_ids', []))
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->values();
        $collectionIds = collect(data_get($discount->rules_json, 'applicable_collection_ids', []))
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->values();

        if ($productIds->isEmpty() && $collectionIds->isEmpty()) {
            return $lines;
        }

        return $lines->filter(function (CartLine $line) use ($productIds, $collectionIds): bool {
            $variant = ProductVariant::withoutGlobalScopes()
                ->with(['product' => fn ($query) => $query->withoutGlobalScopes()->with('collections')])
                ->find($line->variant_id);

            if (! $variant instanceof ProductVariant || $variant->product === null) {
                return false;
            }

            if ($productIds->contains((int) $variant->product_id)) {
                return true;
            }

            return $variant->product->collections
                ->pluck('id')
                ->map(fn (int $id): int => $id)
                ->intersect($collectionIds)
                ->isNotEmpty();
        });
    }

    private function onePerCustomer(Discount $discount): bool
    {
        return (bool) data_get($discount->rules_json, 'one_per_customer', false);
    }

    private function customerHasUsedDiscount(Discount $discount, Cart $cart): bool
    {
        if ($cart->customer_id === null) {
            return false;
        }

        $discountCode = $discount->code ? mb_strtolower($discount->code) : null;

        return OrderLine::query()
            ->whereHas('order', function ($query) use ($discount, $cart): void {
                $query
                    ->withoutGlobalScopes()
                    ->where('store_id', $discount->store_id)
                    ->where('customer_id', $cart->customer_id)
                    ->where('discount_amount', '>', 0);
            })
            ->get()
            ->contains(function (OrderLine $line) use ($discount, $discountCode): bool {
                return collect($line->discount_allocations_json ?? [])
                    ->contains(function (mixed $allocation) use ($discount, $discountCode): bool {
                        $allocationDiscountId = data_get($allocation, 'discount_id');

                        if ($allocationDiscountId !== null && (int) $allocationDiscountId === $discount->getKey()) {
                            return true;
                        }

                        if ($discountCode === null) {
                            return false;
                        }

                        return mb_strtolower((string) data_get($allocation, 'code', '')) === $discountCode;
                    });
            });
    }
}
