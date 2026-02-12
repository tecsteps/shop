<?php

namespace App\Models;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Represents a product collection, not Illuminate\Support\Collection.
 */
class Collection extends Model
{
    use BelongsToStore, HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'store_id',
        'title',
        'handle',
        'description_html',
        'type',
        'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => CollectionType::class,
            'status' => CollectionStatus::class,
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_products')
            ->withPivot('position');
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', CollectionStatus::Active);
    }
}
