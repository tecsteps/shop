<?php

namespace App\Services\Concerns;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Defensive helpers for detecting whether catalog records are referenced by
 * order lines.
 *
 * The `order_lines` table is introduced in Phase 5. Until it exists these
 * checks treat every record as unreferenced, so catalog logic that must guard
 * against destroying order history degrades gracefully during earlier phases.
 */
trait ChecksOrderReferences
{
    /**
     * Whether any order line references one of the product's variants.
     */
    protected function productHasOrderReferences(Product $product): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        $variantIds = $product->variants()->pluck('id');

        if ($variantIds->isEmpty()) {
            return false;
        }

        return DB::table('order_lines')
            ->whereIn('variant_id', $variantIds)
            ->exists();
    }

    /**
     * Whether any order line references a specific variant.
     */
    protected function variantHasOrderReferences(ProductVariant $variant): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        return DB::table('order_lines')
            ->where('variant_id', $variant->id)
            ->exists();
    }
}
