<?php

namespace App\Services\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Store;

class DiscountService
{
    public function findCode(Store $store, string $code): ?Discount
    {
        $discount = Discount::where('store_id', $store->id)
            ->where('type', DiscountType::Code->value)
            ->where('code', $code)
            ->where('status', DiscountStatus::Active->value)
            ->first();

        if (! $discount || ! $discount->isActive()) {
            return null;
        }

        return $discount;
    }

    public function markUsed(Discount $discount): void
    {
        $discount->increment('usage_count');
    }
}
