<?php

namespace App\Models;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    use BelongsToStore;

    /** @use HasFactory<\Database\Factories\CollectionFactory> */
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
        'description_html',
        'type',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CollectionType::class,
            'status' => CollectionStatus::class,
        ];
    }

    /**
     * The products in this collection, ordered by pivot position.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_products')
            ->withPivot('position')
            ->orderBy('collection_products.position');
    }

    /**
     * Limit a query to collections visible on the storefront (active status).
     *
     * @param  Builder<Collection>  $query
     * @return Builder<Collection>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CollectionStatus::Active->value);
    }
}
