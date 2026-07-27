<?php

namespace App\Models;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    /** @use HasFactory<\Database\Factories\CollectionFactory> */
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
     * Get the products in the collection, ordered by position.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_products')
            ->withPivot('position')
            ->orderBy('collection_products.position');
    }
}
