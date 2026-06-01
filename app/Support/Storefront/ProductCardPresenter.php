<?php

namespace App\Support\Storefront;

use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Support\Facades\Storage;

/**
 * Normalizes a catalog Product model into the flat array shape the storefront
 * product-card component consumes.
 *
 * Catalog exposes storefront-facing accessors on the Product model
 * ({@see Product::primaryVariant()}, {@see Product::displayPriceAmount()},
 * {@see Product::compareAtAmount()}, {@see Product::primaryImage()}); this
 * presenter is the thin seam that maps them onto the card's array contract so a
 * card markup change only touches one place. Eager-load `variants` + `media`
 * before mapping a grid to keep those accessors query-free.
 */
class ProductCardPresenter
{
    /**
     * Build the card payload for a product.
     *
     * @return array{
     *     title: string,
     *     handle: ?string,
     *     currency: string,
     *     price_amount: int,
     *     compare_at_amount: ?int,
     *     image_url: ?string,
     *     secondary_image_url: ?string,
     *     sold_out: bool,
     * }
     */
    public static function fromProduct(Product $product): array
    {
        [$primaryImage, $secondaryImage] = self::images($product);

        return [
            'title' => $product->title,
            'handle' => $product->handle,
            'currency' => app()->bound('current_store') ? app('current_store')->default_currency : 'USD',
            'price_amount' => (int) ($product->displayPriceAmount() ?? 0),
            'compare_at_amount' => $product->compareAtAmount(),
            'image_url' => self::mediaUrl($primaryImage),
            'secondary_image_url' => self::mediaUrl($secondaryImage),
            'sold_out' => self::isSoldOut($product),
        ];
    }

    /**
     * The catalog primary image plus a second ready image for the hover
     * crossfade (only when the media relation is already loaded).
     *
     * @return array{0: ?ProductMedia, 1: ?ProductMedia}
     */
    private static function images(Product $product): array
    {
        $primary = $product->primaryImage();

        if ($primary === null || ! $product->relationLoaded('media')) {
            return [$primary, null];
        }

        $secondary = $product->media
            ->where('id', '!=', $primary->id)
            ->sortBy('position')
            ->first();

        return [$primary, $secondary];
    }

    /**
     * Resolve a public URL for a media row, or null.
     */
    private static function mediaUrl(?ProductMedia $media): ?string
    {
        $key = $media?->storage_key;

        return $key ? Storage::disk('public')->url($key) : null;
    }

    /**
     * Whether every variant is out of stock under a deny policy. Falls back to
     * "in stock" when inventory data is not loaded.
     */
    private static function isSoldOut(Product $product): bool
    {
        if (! $product->relationLoaded('variants') || $product->variants->isEmpty()) {
            return false;
        }

        return $product->variants->every(function ($variant): bool {
            // inventoryItem is a HasOne relation; available()/policy are a
            // computed method and an enum cast on that model. Only treat a
            // variant as sold out when inventory is loaded and exhausted under a
            // deny policy.
            $inventory = $variant->relationLoaded('inventoryItem')
                ? $variant->getRelation('inventoryItem')
                : null;

            if ($inventory === null) {
                return false;
            }

            $policy = $inventory->policy;
            $policyValue = $policy instanceof \BackedEnum ? $policy->value : $policy;

            return (int) $inventory->available() <= 0 && $policyValue !== 'continue';
        });
    }
}
