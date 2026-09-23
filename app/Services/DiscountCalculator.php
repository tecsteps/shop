<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Models\Discount;
use Illuminate\Validation\ValidationException;

class DiscountCalculator
{
    /**
     * @param  list<array<string, int|array<int, int>>>  $lines
     * @return array{amount: int, allocations: array<int, int>}
     */
    public function calculate(Discount $discount, array $lines, int $subtotal, bool $hasCustomer = false): array
    {
        $rules = $discount->rules_json ?? [];
        $minimum = (int) ($discount->minimum_subtotal_amount ?? $rules['min_purchase_amount'] ?? $rules['minimum_purchase_amount'] ?? $rules['minimum_order_amount'] ?? 0);

        if (! $discount->is_active || ($discount->starts_at && $discount->starts_at->isFuture()) || ($discount->ends_at && $discount->ends_at->isPast())) {
            throw ValidationException::withMessages(['discount_code' => 'This discount code is expired or inactive.']);
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw ValidationException::withMessages(['discount_code' => 'This discount code has reached its usage limit.']);
        }

        if ($minimum > $subtotal) {
            throw ValidationException::withMessages(['discount_code' => 'Your order does not meet the minimum purchase amount.']);
        }

        if (($rules['customer_eligibility'] ?? null) === 'registered' && ! $hasCustomer) {
            throw ValidationException::withMessages(['discount_code' => 'This discount is available to registered customers.']);
        }

        $eligible = $this->eligibleLines($lines, $rules);
        $eligibleSubtotal = array_sum(array_map(static fn (array $line): int => (int) $line['line_subtotal_amount'], $eligible));

        if ($eligibleSubtotal < 1) {
            throw ValidationException::withMessages(['discount_code' => 'This discount does not apply to the items in your cart.']);
        }

        $amount = match ($discount->type) {
            DiscountType::Percentage->value => intdiv(($eligibleSubtotal * $discount->value) + 50, 100),
            DiscountType::FixedAmount->value => min($discount->value, $eligibleSubtotal),
            DiscountType::FreeShipping->value => 0,
            default => throw ValidationException::withMessages(['discount_code' => 'This discount type is not supported.']),
        };
        $amount = min($amount, $eligibleSubtotal);

        return ['amount' => $amount, 'allocations' => $this->allocate($eligible, $amount, $eligibleSubtotal)];
    }

    /** @param list<array<string, mixed>> $lines */
    private function eligibleLines(array $lines, array $rules): array
    {
        $productIds = array_map('intval', $rules['product_ids'] ?? []);
        $collectionIds = array_map('intval', $rules['collection_ids'] ?? []);

        if ($productIds === [] && $collectionIds === []) {
            return $lines;
        }

        return array_values(array_filter($lines, static function (array $line) use ($productIds, $collectionIds): bool {
            return in_array((int) ($line['product_id'] ?? 0), $productIds, true)
                || array_intersect($collectionIds, array_map('intval', $line['collection_ids'] ?? [])) !== [];
        }));
    }

    /** @param list<array<string, mixed>> $lines
     * @return array<int, int>
     */
    private function allocate(array $lines, int $amount, int $eligibleSubtotal): array
    {
        if ($amount === 0 || $eligibleSubtotal === 0) {
            return [];
        }

        $allocations = [];
        $remainders = [];
        $allocated = 0;

        foreach ($lines as $line) {
            $lineSubtotal = (int) $line['line_subtotal_amount'];
            $numerator = $amount * $lineSubtotal;
            $share = intdiv($numerator, $eligibleSubtotal);
            $key = (int) ($line['variant_id'] ?? $line['id'] ?? 0);
            $allocations[$key] = $share;
            $remainders[$key] = $numerator % $eligibleSubtotal;
            $allocated += $share;
        }

        arsort($remainders);

        foreach (array_keys($remainders) as $key) {
            if ($allocated >= $amount) {
                break;
            }

            $allocations[$key]++;
            $allocated++;
        }

        return $allocations;
    }
}
