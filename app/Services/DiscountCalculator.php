<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class DiscountCalculator
{
    /**
     * @param  list<array<string, int|array<int, int>>>  $lines
     * @return array{amount: int, allocations: array<int, int>}
     */
    public function calculate(
        Discount $discount,
        array $lines,
        int $subtotal,
        bool $hasCustomer = false,
        ?int $customerId = null,
        ?string $customerEmail = null,
    ): array {
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

        if (($rules['one_per_customer'] ?? false) && $this->hasCustomerRedeemed($discount, $customerId, $customerEmail)) {
            throw ValidationException::withMessages(['discount_code' => 'This discount can only be used once per customer.']);
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

    /**
     * @param  list<Discount>  $discounts
     * @param  list<array<string, mixed>>  $lines
     * @return array{amount: int, allocations: array<int, int>, applied_discounts: list<array{discount_id: int|null, code: string|null, title: string, type: string, activation_method: string, amount: int, allocations: array<int, int>}>}
     */
    public function calculateStacked(
        array $discounts,
        array $lines,
        int $subtotal,
        ?int $customerId = null,
        ?string $customerEmail = null,
        bool $hasCustomer = false,
    ): array {
        $remainingLines = $lines;
        $totalAmount = 0;
        $allocations = [];
        $appliedDiscounts = [];

        foreach ($discounts as $discount) {
            try {
                $result = $this->calculate($discount, $remainingLines, $subtotal, $hasCustomer, $customerId, $customerEmail);
            } catch (ValidationException $exception) {
                if ($discount->code !== null) {
                    throw $exception;
                }

                continue;
            }

            foreach ($remainingLines as $index => $line) {
                $variantId = (int) ($line['variant_id'] ?? $line['id'] ?? 0);
                $lineDiscount = (int) ($result['allocations'][$variantId] ?? 0);

                if ($lineDiscount > 0) {
                    $allocations[$variantId] = (int) ($allocations[$variantId] ?? 0) + $lineDiscount;
                    $remainingLines[$index]['line_subtotal_amount'] = max(0, (int) $line['line_subtotal_amount'] - $lineDiscount);
                }
            }

            $totalAmount += $result['amount'];
            $appliedDiscounts[] = [
                'discount_id' => $discount->exists ? (int) $discount->getKey() : null,
                'code' => $discount->code,
                'title' => $discount->title ?? '',
                'type' => $discount->type,
                'activation_method' => ($discount->rules_json['activation_method'] ?? null) === 'automatic' || $discount->code === null ? 'automatic' : 'code',
                'amount' => $result['amount'],
                'allocations' => $result['allocations'],
            ];
        }

        return [
            'amount' => $totalAmount,
            'allocations' => $allocations,
            'applied_discounts' => $appliedDiscounts,
        ];
    }

    public function hasCustomerRedeemed(Discount $discount, ?int $customerId, ?string $customerEmail): bool
    {
        if (! $discount->exists || ($customerId === null && blank($customerEmail))) {
            return false;
        }

        $query = Order::withoutGlobalScopes()
            ->where('store_id', $discount->store_id)
            ->whereHas('checkout', function (Builder $checkoutQuery) use ($discount): void {
                $checkoutQuery->withoutGlobalScopes()->where(function (Builder $query) use ($discount): void {
                    if ($discount->exists) {
                        $query->whereJsonContains('totals_json->applied_discount_ids', (int) $discount->getKey());
                    }

                    if ($discount->code !== null) {
                        $query->orWhere('discount_code', $discount->code);
                    }
                });
            })
            ->where(function (Builder $query) use ($customerId, $customerEmail): void {
                if ($customerId !== null) {
                    $query->where('customer_id', $customerId);
                }

                if (filled($customerEmail)) {
                    $normalizedEmail = mb_strtolower(trim((string) $customerEmail));

                    if ($customerId !== null) {
                        $query->orWhereRaw('LOWER(email) = ?', [$normalizedEmail]);
                    } else {
                        $query->whereRaw('LOWER(email) = ?', [$normalizedEmail]);
                    }
                }
            });

        return $query->exists();
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
