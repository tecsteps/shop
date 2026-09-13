<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * Read-only storefront catalog helpers (visibility, pricing, stock).
 */
class StorefrontCatalog
{
    /**
     * @return Builder<Product>
     */
    public function visibleProducts(Store $store): Builder
    {
        return Product::query()
            ->where('store_id', $store->getKey())
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at');
    }

    public function withStorefrontRelations(Builder $query): Builder
    {
        return $query->with([
            'productVariants' => fn ($query) => $query->orderBy('position')->orderBy('id'),
            'productVariants.inventoryItem',
            'productVariants.optionValues',
            'productMedia' => fn ($query) => $query->orderBy('position')->orderBy('id'),
        ]);
    }

    public function displayVariant(Product $product): ?ProductVariant
    {
        $active = $product->productVariants->where('status', VariantStatus::Active);

        return $active->firstWhere('is_default', true)
            ?? $active->sortBy(fn (ProductVariant $variant): array => [$variant->price_amount, $variant->position, $variant->getKey()])->first();
    }

    public function minPrice(Product $product): int
    {
        return $this->displayVariant($product)?->price_amount ?? 0;
    }

    public function isInStock(Product $product): bool
    {
        foreach ($product->productVariants as $variant) {
            if ($variant->status !== VariantStatus::Active) {
                continue;
            }

            $item = $variant->inventoryItem;

            if ($item === null) {
                return true;
            }

            if ($item->policy === InventoryPolicy::Continue) {
                return true;
            }

            if (($item->quantity_on_hand - $item->quantity_reserved) > 0) {
                return true;
            }
        }

        return false;
    }

    public function availableQuantity(ProductVariant $variant): ?int
    {
        $item = $variant->inventoryItem;

        if ($item === null) {
            return null;
        }

        if ($item->policy === InventoryPolicy::Continue) {
            return null;
        }

        return max(0, $item->quantity_on_hand - $item->quantity_reserved);
    }

    public function primaryImageUrl(Product $product): ?string
    {
        $media = $product->productMedia->first();

        if ($media === null) {
            return null;
        }

        // Relative URL: storefronts run on per-store hostnames while the
        // storage URL is built from APP_URL, so absolute URLs leak a
        // cross-host address into same-origin <img> tags.
        return parse_url(Storage::disk('public')->url($media->storage_key), PHP_URL_PATH)
            ?: Storage::disk('public')->url($media->storage_key);
    }

    public function primaryImageAlt(Product $product): string
    {
        return (string) ($product->productMedia->first()?->alt_text ?? $product->title);
    }

    /**
     * @param  array<int>  $productIds
     * @return array<int, int> product_id => units sold
     */
    public function salesCounts(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        return OrderLine::query()
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) AS total')
            ->pluck('total', 'product_id')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    public function variantOptionLabels(ProductVariant $variant): string
    {
        return $variant->optionValues->pluck('value')->implode(' / ');
    }
}
