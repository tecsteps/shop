<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Store;
use App\ValueObjects\DiscountResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DiscountService
{
    public function validate(string $code, Store $store, Cart $cart): Discount
    {
        $discount = Discount::query()
            ->where('store_id', $store->id)
            ->whereRaw('LOWER(code) = ?', [strtolower(trim($code))])
            ->first();

        if ($discount === null) {
            throw InvalidDiscountException::notFound();
        }

        if ($discount->status === DiscountStatus::Disabled) {
            throw InvalidDiscountException::disabled();
        }

        if ($discount->status !== DiscountStatus::Active) {
            throw InvalidDiscountException::expired();
        }

        $now = now();

        if ($discount->starts_at !== null && $discount->starts_at->greaterThan($now)) {
            throw InvalidDiscountException::notYetActive();
        }

        if ($discount->ends_at !== null && $discount->ends_at->lessThan($now)) {
            throw InvalidDiscountException::expired();
        }

        if ($discount->usage_limit !== null && (int) $discount->usage_count >= (int) $discount->usage_limit) {
            throw InvalidDiscountException::usageLimitReached();
        }

        $rules = $discount->rules_json ?? [];
        $minPurchase = $rules['min_purchase_amount'] ?? null;

        if ($minPurchase !== null) {
            $lines = $cart->relationLoaded('lines') ? $cart->lines : $cart->lines()->get();
            $subtotal = (int) $lines->sum('line_subtotal_amount');

            if ($subtotal < (int) $minPurchase) {
                throw InvalidDiscountException::minimumNotMet();
            }
        }

        return $discount;
    }

    /**
     * @param  Collection<int, \App\Models\CartLine>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\CartLine>  $lines
     */
    public function calculate(Discount $discount, int $subtotal, Collection|\Illuminate\Database\Eloquent\Collection $lines): DiscountResult
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return new DiscountResult(amount: 0, allocations: [], freeShipping: true);
        }

        if ($subtotal <= 0 || $lines->isEmpty()) {
            return new DiscountResult(amount: 0, allocations: []);
        }

        $totalDiscount = match ($discount->value_type) {
            DiscountValueType::Percent => (int) floor($subtotal * (int) $discount->value_amount / 100),
            DiscountValueType::Fixed => min((int) $discount->value_amount, $subtotal),
            default => 0,
        };

        if ($totalDiscount <= 0) {
            return new DiscountResult(amount: 0, allocations: []);
        }

        $allocations = [];
        $remaining = $totalDiscount;

        $lineList = $lines->values();
        $lastIndex = $lineList->count() - 1;

        foreach ($lineList as $index => $line) {
            if ($index === $lastIndex) {
                $allocations[(int) $line->id] = $remaining;
                break;
            }

            $lineSubtotal = (int) $line->line_subtotal_amount;
            $lineDiscount = (int) round($totalDiscount * $lineSubtotal / $subtotal);
            $allocations[(int) $line->id] = $lineDiscount;
            $remaining -= $lineDiscount;
        }

        return new DiscountResult(amount: $totalDiscount, allocations: $allocations);
    }

    public function recordUsage(Discount $discount): void
    {
        DB::transaction(function () use ($discount): void {
            $discount->refresh();
            $discount->usage_count = (int) $discount->usage_count + 1;
            $discount->save();
        });
    }
}
