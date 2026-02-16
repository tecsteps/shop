<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'title',
        'handle',
        'description_html',
        'status',
        'vendor',
        'product_type',
        'tags',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'tags' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** @return HasMany<ProductOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    /** @return HasMany<ProductMedia, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class);
    }

    /** @return BelongsToMany<Collection, $this> */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_products')
            ->withPivot('position');
    }

    /** @return HasMany<ProductVariant, $this> */
    public function defaultVariant(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_default', true);
    }
}
