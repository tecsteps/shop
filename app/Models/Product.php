<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = ['store_id', 'title', 'handle', 'description', 'description_html', 'vendor', 'product_type', 'tags', 'status', 'published_at', 'sales_count', 'metadata'];

    protected function casts(): array
    {
        return ['status' => ProductStatus::class, 'tags' => 'array', 'metadata' => 'array', 'published_at' => 'datetime'];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('position');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('position');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_products')->withPivot('position');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_lines');
    }

    public function scopePublished($query): void
    {
        $query->where('status', ProductStatus::Active)->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function defaultVariant(): ?ProductVariant
    {
        return $this->variants->firstWhere('is_default', true) ?? $this->variants->first();
    }
}
