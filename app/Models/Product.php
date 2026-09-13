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
    use BelongsToStore, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
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
            'id' => 'integer',
            'store_id' => 'integer',
            'status' => ProductStatus::class,
            'tags' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function productOptions(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function productMedia(): HasMany
    {
        return $this->hasMany(ProductMedia::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_products')->withPivot('position');
    }
}
