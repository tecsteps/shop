<?php

namespace App\Models;

use App\Enums\CollectionStatus;
use App\Models\Concerns\BelongsToStore;
use Database\Factories\CollectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    /** @use HasFactory<CollectionFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'title',
        'handle',
        'description_html',
        'type',
        'status',
    ];

    protected $attributes = [
        'type' => 'manual',
        'status' => CollectionStatus::Active->value,
    ];

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_products')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => CollectionStatus::class];
    }
}
