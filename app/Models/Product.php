<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use BelongsToStore, HasFactory;

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
     * Register model event hooks.
     */
    protected static function booted(): void
    {
        // Delete media records through Eloquent so the file-cleanup hook on
        // ProductMedia fires (database cascade would bypass model events).
        static::deleting(function (Product $product): void {
            $product->media->each->delete();
        });
    }

    /**
     * Get the variants of the product.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    /**
     * Get the options of the product.
     *
     * @return HasMany<ProductOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('position');
    }

    /**
     * Get the media attached to the product.
     *
     * @return HasMany<ProductMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('position');
    }

    /**
     * Get the collections containing the product.
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
     * Get the default variant of the product.
     *
     * @return HasOne<ProductVariant, $this>
     */
    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    /**
     * Scope to products visible on the storefront: active and published.
     *
     * @param  Builder<Product>  $query
     */
    protected function scopeVisible(Builder $query): void
    {
        $query->where('status', ProductStatus::Active)->whereNotNull('published_at');
    }
}
