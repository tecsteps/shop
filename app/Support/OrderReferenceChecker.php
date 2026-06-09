<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderReferenceChecker
{
    /**
     * Whether any order line references the product or one of its variants.
     *
     * The order_lines table ships in Phase 5; until it exists no product can
     * have order references, so this safely returns false.
     */
    public function productHasOrderReferences(Product $product): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        return DB::table('order_lines')
            ->where('product_id', $product->getKey())
            ->orWhereIn('variant_id', $product->variants()->pluck('id'))
            ->exists();
    }

    /**
     * Whether any order line references the variant.
     */
    public function variantHasOrderReferences(ProductVariant $variant): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        return DB::table('order_lines')
            ->where('variant_id', $variant->getKey())
            ->exists();
    }
}
