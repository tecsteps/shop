<?php

namespace App\Models;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $store_id
 * @property string $title
 * @property string $handle
 * @property string|null $description_html
 * @property CollectionType $type
 * @property CollectionStatus $status
 */
class Collection extends Model
{
    /** @use HasFactory<\Database\Factories\CollectionFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'title',
        'handle',
        'description_html',
        'type',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CollectionStatus::class,
            'type' => CollectionType::class,
        ];
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_products')
            ->withPivot('position');
    }
}
