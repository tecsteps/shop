<?php

namespace App\Models;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToStore;

    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'title',
        'handle',
        'status',
        'description_html',
        'vendor',
        'product_type',
        'tags',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'tags' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * The variants (purchasable SKUs) of this product.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * The default variant of this product.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function defaultVariant(): HasMany
    {
        return $this->variants()->where('is_default', true);
    }

    /**
     * The option dimensions (e.g. Size, Color) of this product.
     *
     * @return HasMany<ProductOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('position');
    }

    /**
     * The media (images, videos) attached to this product.
     *
     * @return HasMany<ProductMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('position');
    }

    /**
     * The collections this product belongs to.
     *
     * @return BelongsToMany<Collection, $this>
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_products')
            ->withPivot('position')
            ->orderBy('collection_products.position');
    }

    /**
     * Limit a query to products visible on the storefront (active status).
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active->value);
    }

    /**
     * The variant a product card should price and link to: the default variant
     * if flagged, otherwise the cheapest active variant.
     *
     * Loads from the already-loaded `variants` relation when present to avoid an
     * extra query in grids; falls back to a scoped query otherwise.
     */
    public function primaryVariant(): ?ProductVariant
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants
                ->sortByDesc(fn (ProductVariant $variant): int => $variant->is_default ? 1 : 0)
                ->sortBy('price_amount')
                ->first();
        }

        return $this->variants()
            ->where('status', VariantStatus::Active->value)
            ->orderByDesc('is_default')
            ->orderBy('price_amount')
            ->first();
    }

    /**
     * The display price in minor units (cents) for a product card: the primary
     * variant's price, or null when the product has no variants.
     */
    public function displayPriceAmount(): ?int
    {
        return $this->primaryVariant()?->price_amount;
    }

    /**
     * The "compare at" (was) price in minor units for a product card, or null
     * when the primary variant is not on sale.
     */
    public function compareAtAmount(): ?int
    {
        return $this->primaryVariant()?->compare_at_amount;
    }

    /**
     * The primary image for a product card: the first ready image media by
     * position, or null when none has finished processing.
     */
    public function primaryImage(): ?ProductMedia
    {
        if ($this->relationLoaded('media')) {
            return $this->media
                ->where('type', MediaType::Image)
                ->where('status', MediaStatus::Ready)
                ->sortBy('position')
                ->first();
        }

        return $this->media()
            ->where('type', MediaType::Image->value)
            ->where('status', MediaStatus::Ready->value)
            ->first();
    }
}
